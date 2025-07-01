<?php

namespace wcf\system\endpoint\controller\core\conversations;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\data\conversation\Conversation;
use wcf\http\Helper;
use wcf\system\endpoint\IController;
use wcf\system\endpoint\PostRequest;
use wcf\system\exception\PermissionDeniedException;

/**
 * API endpoint for restore a conversation.
 *
 * @author Olaf Braun
 * @copyright 2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
#[PostRequest('/core/conversations/{id:\d+}/restore')]
final class RestoreConversation implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $conversation = Helper::fetchObjectFromRequestParameter($variables['id'], Conversation::class);
        $this->assertConversationIsAccessible($conversation);

        (new \wcf\system\conversation\command\LeaveConversation([$conversation->conversationID], Conversation::STATE_DEFAULT))();

        return new JsonResponse([]);
    }

    private function assertConversationIsAccessible(Conversation $conversation): void
    {
        if (!Conversation::isParticipant([$conversation->conversationID])) {
            throw new PermissionDeniedException();
        }
    }
}
