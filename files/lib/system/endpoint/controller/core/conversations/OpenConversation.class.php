<?php

namespace wcf\system\endpoint\controller\core\conversations;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationEditor;
use wcf\http\Helper;
use wcf\system\endpoint\IController;
use wcf\system\endpoint\PostRequest;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\log\modification\ConversationModificationLogHandler;
use wcf\system\WCF;

/**
 * Opens the conversation with the given ID for new messages.
 *
 * @author Olaf Braun
 * @copyright 2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
#[PostRequest('/core/conversations/{id:\d+}/open')]
final class OpenConversation implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $conversation = Helper::fetchObjectFromRequestParameter($variables['id'], Conversation::class);
        $this->assertConversationCanOpened($conversation);

        if ($conversation->isClosed === 1) {
            $this->openConversation($conversation);
        }

        return new JsonResponse([]);
    }

    private function assertConversationCanOpened(Conversation $conversation): void
    {
        if (!Conversation::isParticipant([$conversation->conversationID])) {
            throw new PermissionDeniedException();
        }

        if ($conversation->userID !== WCF::getUser()->userID) {
            throw new PermissionDeniedException();
        }
    }

    private function openConversation(Conversation $conversation): void
    {
        $editor = new ConversationEditor($conversation);
        $editor->update(['isClosed' => 0]);

        ConversationModificationLogHandler::getInstance()->open($conversation);
    }
}
