<?php

namespace wcf\system\endpoint\controller\core\conversations;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\message\ConversationMessageList;
use wcf\http\Helper;
use wcf\system\endpoint\GetRequest;
use wcf\system\endpoint\IController;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Retrieves the HTML code for the popover of the conversation with the given ID.
 *
 * @author      Marcel Werk
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 */
#[GetRequest('/core/conversations/{id:\d+}/popover')]
final class GetConversationPopover implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $conversation = Helper::fetchObjectFromRequestParameter($variables['id'], Conversation::class);

        $this->assertConversationIsAccessible($conversation);

        return new JsonResponse([
            'template' => $this->renderPopover($conversation),
        ]);
    }

    private function assertConversationIsAccessible(Conversation $conversation): void
    {
        if (!Conversation::isParticipant([$conversation->conversationID])) {
            throw new PermissionDeniedException();
        }
    }

    private function renderPopover(Conversation $conversation): string
    {
        $messageList = new ConversationMessageList();
        $messageList->getConditionBuilder()
            ->add("conversation_message.messageID = ?", [$conversation->firstMessageID]);
        $messageList->readObjects();
        $message = $messageList->getSingleObject();
        if ($message === null) {
            return '';
        }

        return WCF::getTPL()->render('wcf', 'conversationMessagePopover', [
            'message' => $message,
        ]);
    }
}
