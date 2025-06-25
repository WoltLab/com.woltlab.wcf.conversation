<?php

namespace wcf\system\endpoint\controller\core\conversations;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\data\conversation\Conversation;
use wcf\http\Helper;
use wcf\system\endpoint\GetRequest;
use wcf\system\endpoint\IController;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * API endpoint for rendering the conversation leave dialog.
 *
 * @author      Olaf Braun
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 *
 * TODO remove
 */
#[GetRequest('/core/conversations/{id:\d+}/leave-dialog')]
final class GetConversationLeaveDialog implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $conversation = Helper::fetchObjectFromRequestParameter($variables['id'], Conversation::class);

        $this->assertConversationIsAccessible($conversation);

        return new JsonResponse([
            'template' => WCF::getTPL()->render('wcf', 'conversationLeave', [
                'hideConversation' => $this->isConversationHidden($conversation),
            ])
        ]);
    }

    private function assertConversationIsAccessible(Conversation $conversation): void
    {
        if (!Conversation::isParticipant([$conversation->conversationID])) {
            throw new PermissionDeniedException();
        }
    }

    private function isConversationHidden(Conversation $conversation): bool
    {
        $sql = "SELECT  hideConversation
                FROM    wcf1_conversation_to_user
                WHERE   conversationID = ?
                    AND participantID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([
            $conversation->conversationID,
            WCF::getUser()->userID,
        ]);

        return \boolval($statement->fetchSingleColumn());
    }
}
