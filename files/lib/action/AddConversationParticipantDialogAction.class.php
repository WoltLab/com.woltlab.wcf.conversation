<?php

namespace wcf\action;

use CuyZ\Valinor\Mapper\MappingError;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use wcf\command\conversation\AddConversationParticipant;
use wcf\data\conversation\Conversation;
use wcf\data\user\group\UserGroup;
use wcf\http\Helper;
use wcf\system\conversation\TConversationForm;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\form\builder\field\MultipleSelectionFormField;
use wcf\system\form\builder\field\RadioButtonFormField;
use wcf\system\form\builder\field\user\UserFormField;
use wcf\system\form\builder\Psr15DialogForm;
use wcf\system\WCF;

/**
 * Form dialog to add participants to a conversation.
 *
 * @author Olaf Braun
 * @copyright 2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
final class AddConversationParticipantDialogAction implements RequestHandlerInterface
{
    use TConversationForm;

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $parameters = Helper::mapQueryParameters(
                $request->getQueryParams(),
                <<<'EOT'
                array {
                    id: positive-int,
                }
                EOT
            );
        } catch (MappingError) {
            throw new IllegalLinkException();
        }

        $conversation = new Conversation($parameters['id']);

        if (!Conversation::isParticipant([$conversation->conversationID]) || !$conversation->canAddParticipants()) {
            throw new PermissionDeniedException();
        }

        $form = $this->getForm($conversation);

        if ($request->getMethod() === 'GET') {
            return $form->toResponse();
        } elseif ($request->getMethod() === 'POST') {
            $response = $form->validateRequest($request);
            if ($response !== null) {
                return $response;
            }

            $data = $form->getData();

            $messageVisibility = $data['data']['messageVisibility'] ?? 'new';
            $participants = $data['participants'] ?? [];
            if (isset($data['participantGroups'])) {
                $groupIDs = $data['participantGroups'];
                $participants = \array_unique(
                    \array_merge(
                        $participants,
                        $this->getUserByGroups($groupIDs)
                    )
                );
            }

            $participants = $this->filterOutParticipantsAlreadyAdded($participants, $conversation);

            (new AddConversationParticipant($conversation, $participants, $messageVisibility))();

            return new JsonResponse([]);
        } else {
            throw new \LogicException('Unreachable');
        }
    }

    /**
     * @param int[] $participants
     *
     * @return int[]
     */
    private function filterOutParticipantsAlreadyAdded(array $participants, Conversation $conversation): array
    {
        $alreadyParticipantIDs = $conversation->getParticipantIDs(true);

        return \array_filter($participants, static function (int $userID) use ($alreadyParticipantIDs): bool {
            return !\in_array($userID, $alreadyParticipantIDs, true);
        });
    }

    private function getForm(Conversation $conversation): Psr15DialogForm
    {
        $form = new Psr15DialogForm(
            static::class,
            WCF::getLanguage()->get('wcf.conversation.edit.addParticipants')
        );

        $groupParticipants = \array_filter(
            UserGroup::getSortedGroupsByType(),
            // @phpstan-ignore property.notFound
            static fn(UserGroup $group) => $group->canBeAddedAsConversationParticipant
        );

        $form->appendChildren([
            UserFormField::create('participants')
                ->label('wcf.conversation.participants')
                ->maximumMultiples(WCF::getSession()->getPermission('user.conversation.maxParticipants'))
                ->multiple()
                ->maximumMultiples(WCF::getSession()->getPermission('user.conversation.maxParticipants') - $conversation->participants)
                ->addValidator($this->getParticipantsValidator())
                ->addValidator($this->getMaximumParticipantsValidator(invisibleParticipantGroupsFieldId: null)),
            MultipleSelectionFormField::create('participantGroups')
                ->label('wcf.conversation.participantGroups')
                ->available(WCF::getSession()->getPermission('user.conversation.canAddGroupParticipants')
                    && \count($groupParticipants) > 0)
                ->filterable(\count($groupParticipants) > 20)
                ->options($groupParticipants),
            RadioButtonFormField::create('messageVisibility')
                ->label('wcf.conversation.visibility')
                ->available(!$conversation->isDraft && $conversation->canAddParticipantsUnrestricted())
                ->required()
                ->options([
                    'all' => 'wcf.conversation.visibility.all',
                    'new' => 'wcf.conversation.visibility.new',
                ])
                ->value('all'),
        ]);

        $form->markRequiredFields(false);
        $form->build();

        return $form;
    }
}
