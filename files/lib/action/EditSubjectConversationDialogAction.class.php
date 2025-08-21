<?php

namespace wcf\action;

use CuyZ\Valinor\Mapper\MappingError;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use wcf\data\conversation\Conversation;
use wcf\http\Helper;
use wcf\system\conversation\command\SetConversationSubject;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\Psr15DialogForm;
use wcf\system\WCF;

/**
 * Form dialog to edit the subject of a conversation.
 *
 * @author Olaf Braun
 * @copyright 2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
final class EditSubjectConversationDialogAction implements RequestHandlerInterface
{
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

        if ($conversation->userID !== WCF::getUser()->userID) {
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
            $data = $form->getData()['data'];

            (new SetConversationSubject($conversation, $data['subject']))();

            return new JsonResponse([]);
        } else {
            throw new \LogicException('Unreachable');
        }
    }

    private function getForm(Conversation $conversation): Psr15DialogForm
    {
        $form = new Psr15DialogForm(
            static::class,
            WCF::getLanguage()->get('wcf.conversation.edit.subject')
        );

        $form->appendChildren([
            TextFormField::create('subject')
                ->label('wcf.global.subject')
                ->maximumLength(255)
                ->autoFocus()
                ->censorship()
                ->required(),
        ]);

        $form->markRequiredFields(false);
        $form->updatedObject($conversation);
        $form->build();

        return $form;
    }
}
