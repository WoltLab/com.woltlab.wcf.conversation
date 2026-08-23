<?php

namespace wcf\system\endpoint\controller\core\conversations;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationParticipantList;
use wcf\http\Helper;
use wcf\system\endpoint\GetRequest;
use wcf\system\endpoint\IController;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Retrieves the HTML code for the list of participants of the conversation with the given ID.
 *
 * @author Olaf Braun
 * @copyright 2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
#[GetRequest('/core/conversations/{conversationId:\d+}/participants')]
final class GetConversationParticipantList implements IController
{
    use TConversationEndpoint;

    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $this->assertConversationsAreEnabled();

        $conversation = Helper::fetchObjectFromRequestParameter($variables['conversationId'], Conversation::class);

        $this->assertCanRetrieveParticipantList($conversation);

        return new JsonResponse([
            'template' => WCF::getTPL()->render('wcf', 'conversationParticipantList', [
                'conversation' => $conversation,
                'participants' => $this->getParticipantList($conversation),
            ]),
        ]);
    }

    private function assertCanRetrieveParticipantList(Conversation $conversation): void
    {
        if (!Conversation::isParticipant([$conversation->conversationID])) {
            throw new PermissionDeniedException();
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
