<?php

namespace wcf\action;

use CuyZ\Valinor\Mapper\MappingError;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use wcf\data\conversation\Conversation;
use wcf\event\message\MessageSpamChecking;
use wcf\http\Helper;
use wcf\command\conversation\SetConversationSubject;
use wcf\system\event\EventHandler;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\Psr15DialogForm;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\WCF;
use wcf\util\UserUtil;

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
        if (\MODULE_CONVERSATION === 0) {
            throw new IllegalLinkException();
        }

        if (!WCF::getSession()->getPermission('user.conversation.canUseConversation')) {
            throw new PermissionDeniedException();
        }

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

        $conversation = Helper::fetchObjectFromRequestParameter($parameters['id'], Conversation::class);

        if ($conversation->userID !== WCF::getUser()->userID) {
            throw new PermissionDeniedException();
        }

        if (!Conversation::isParticipant([$conversation->conversationID])) {
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

            $this->assertIsNotSpam($conversation, $data['subject']);

            (new SetConversationSubject($conversation, $data['subject']))();

            return new JsonResponse([]);
        } else {
            throw new \LogicException('Unreachable');
        }
    }

    private function assertIsNotSpam(Conversation $conversation, string $subject): void
    {
        $message = $conversation->getFirstMessage();
        \assert($message !== null);

        // The event expects the processor of the message that is being written,
        // but only the subject is editable here. The unchanged first message is
        // passed to provide the context of the conversation.
        $htmlInputProcessor = new HtmlInputProcessor();
        $htmlInputProcessor->process(
            $message->message,
            'com.woltlab.wcf.conversation.message',
            $message->messageID
        );

        $event = new MessageSpamChecking(
            $htmlInputProcessor,
            WCF::getUser(),
            UserUtil::getIpAddress(),
            $subject,
        );
        EventHandler::getInstance()->fire($event);

        if ($event->defaultPrevented()) {
            throw new PermissionDeniedException();
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
