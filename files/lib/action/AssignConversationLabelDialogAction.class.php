<?php

namespace wcf\action;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\label\ConversationLabel;
use wcf\http\Helper;
use wcf\system\conversation\command\AssignConversationLabel;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\form\builder\field\MultipleSelectionFormField;
use wcf\system\form\builder\Psr15DialogForm;
use wcf\system\WCF;

/**
 * Action for assigning label to a conversation.
 *
 * @author Olaf Braun
 * @copyright 2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
final class AssignConversationLabelDialogAction implements RequestHandlerInterface
{
    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = Helper::mapQueryParameters(
            $request->getQueryParams(),
            <<<'EOT'
                array {
                    id?: positive-int,
                    objectIDs?: positive-int[]
                }
                EOT
        );

        if (!isset($parameters['id']) && !isset($parameters['objectIDs'])) {
            throw new IllegalLinkException();
        }

        $conversationIDs = $parameters['objectIDs'] ?? [$parameters['id']];

        if ($conversationIDs === []) {
            throw new IllegalLinkException();
        }

        if (!Conversation::isParticipant($conversationIDs)) {
            throw new PermissionDeniedException();
        }

        $labels = ConversationLabel::getUserLabels();
        if ($labels === []) {
            throw new IllegalLinkException();
        }

        $form = $this->getForm($conversationIDs, $labels);

        if ($request->getMethod() === 'GET') {
            return $form->toResponse();
        } elseif ($request->getMethod() === 'POST') {
            $response = $form->validateRequest($request);
            if ($response !== null) {
                return $response;
            }
            $labelIDs = $form->getData()['labelIDs'] ?? [];

            (new AssignConversationLabel(\array_keys($labels), $conversationIDs, $labelIDs))();

            return new JsonResponse([]);
        } else {
            throw new \LogicException('Unreachable');
        }
    }

    /**
     * @param int[] $conversationIDs
     * @param array<int, ConversationLabel> $labels
     */
    private function getForm(array $conversationIDs, array $labels): Psr15DialogForm
    {
        $form = new Psr15DialogForm(
            static::class,
            WCF::getLanguage()->get('wcf.conversation.label.assignLabels')
        );

        $form->appendChildren([
            MultipleSelectionFormField::create('labelIDs')
                ->options(
                    \array_map(static fn(ConversationLabel $label) => $label->render(), $labels)
                )
                ->value($this->getSelectedLabelIDs($conversationIDs, \array_keys($labels))),
        ]);

        $form->markRequiredFields(false);
        $form->build();

        return $form;
    }

    /**
     * @param int[] $conversationIDs
     * @param int[] $labelIDs
     *
     * @return int[]
     */
    private function getSelectedLabelIDs(array $conversationIDs, array $labelIDs): array
    {
        if (\count($conversationIDs) !== 1) {
            return [];
        }

        $conditionBuilder = new PreparedStatementConditionBuilder();
        $conditionBuilder->add('conversationID = ?', [\reset($conversationIDs)]);
        $conditionBuilder->add('labelID IN (?)', [$labelIDs]);

        $sql = "SELECT  labelID
                FROM    wcf1_conversation_label_to_object
                {$conditionBuilder}";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditionBuilder->getParameters());

        return $statement->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }
}
