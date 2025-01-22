<?php

namespace wcf\system\endpoint\controller\core\conversations;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\data\conversation\Conversation;
use wcf\http\Helper;
use wcf\system\conversation\command\Leave;
use wcf\system\endpoint\IController;
use wcf\system\endpoint\PostRequest;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\request\LinkHandler;

/**
 * API endpoint for leaving a conversation.
 *
 * @author      Olaf Braun
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 */
#[PostRequest('/core/conversations/{id:\d+}/leave')]
final class LeaveConversation implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $conversation = Helper::fetchObjectFromRequestParameter($variables['id'], Conversation::class);
        $this->assertConversationIsAccessible($conversation);

        $parameters = Helper::mapApiParameters($request, LeaveConversationParameters::class);
        $hideConversation = $parameters->hideConversation;

        (new Leave([$conversation->conversationID], $hideConversation))();

        return new JsonResponse([
            'redirectUrl' => LinkHandler::getInstance()->getLink('ConversationList'),
        ]);
    }

    private function assertConversationIsAccessible(Conversation $conversation): void
    {
        if (!Conversation::isParticipant([$conversation->conversationID])) {
            throw new PermissionDeniedException();
        }
    }
}

/** @internal */
final class LeaveConversationParameters
{
    public function __construct(
        /** @var Conversation::STATE_DEFAULT|Conversation::STATE_HIDDEN|Conversation::STATE_LEFT */
        public readonly int $hideConversation,
    ) {
    }
}
