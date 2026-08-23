<?php

namespace wcf\system\endpoint\controller\core\conversations;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationParticipantList;
use wcf\http\Helper;
use wcf\system\endpoint\DeleteRequest;
use wcf\system\endpoint\IController;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Removes a participant from the conversation with the given ID.
 *
 * @author Olaf Braun
 * @copyright 2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
#[DeleteRequest('/core/conversations/{conversationId:\d+}/participants/{participantId:\d+}')]
final class RemoveConversationParticipant implements IController
{
    use TConversationEndpoint;

    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $this->assertConversationsAreEnabled();

        $conversation = Helper::fetchObjectFromRequestParameter($variables['conversationId'], Conversation::class);
        $participantUserID = \intval($variables['participantId']);

        $this->assertCanRemoveParticipant($conversation, $participantUserID);

        (new \wcf\command\conversation\RemoveConversationParticipant($conversation, $participantUserID))();

        return new JsonResponse([
            'template' => WCF::getTPL()->render('wcf', 'conversationParticipantList', [
                'conversation' => $conversation,
                'participants' => $this->getParticipantList($conversation),
            ]),
        ]);
    }

    private function assertCanRemoveParticipant(Conversation $conversation, int $participantUserID): void
    {
        if (!Conversation::isParticipant([$conversation->conversationID])) {
            throw new PermissionDeniedException();
        }

        if ($conversation->userID !== WCF::getUser()->userID) {
            throw new PermissionDeniedException();
        }

        if ($participantUserID === WCF::getUser()->userID) {
            throw new IllegalLinkException();
        }

        $participantUserIDs = $conversation->getParticipantIDs(true);
        if (!\in_array($participantUserID, $participantUserIDs)) {
            throw new IllegalLinkException();
        }
    }

    private function getParticipantList(Conversation $conversation): ConversationParticipantList
    {
        $participantList = new ConversationParticipantList(
            $conversation->conversationID,
            WCF::getUser()->userID,
            $conversation->userID === WCF::getUser()->userID
        );
        $participantList->readObjects();

        return $participantList;
    }
}
