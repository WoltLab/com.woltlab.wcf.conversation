<?php

namespace wcf\system\endpoint\controller\core\conversations;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\label\ConversationLabelList;
use wcf\http\Helper;
use wcf\system\clipboard\ClipboardHandler;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\endpoint\IController;
use wcf\system\endpoint\PostRequest;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * API endpoint for assigning labels to conversations.
 *
 * @author      Olaf Braun
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 */
#[PostRequest('/core/conversations/assign-labels')]
final class AssignConversationLabels implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $parameters = Helper::mapApiParameters($request, AssignConversationLabelsParameters::class);
        $conversationIDs = $parameters->conversationIDs;

        if ($conversationIDs === []) {
            throw new IllegalLinkException();
        }
        if (!Conversation::isParticipant($conversationIDs)) {
            throw new PermissionDeniedException();
        }

        $labelIDs = $parameters->labelIDs;

        $labelList = ConversationLabel::getLabelsByUser();
        if (!\count($labelList)) {
            throw new IllegalLinkException();
        }

        foreach ($labelIDs as $labelID) {
            if (!\in_array($labelID, $labelList->getObjectIDs())) {
                throw new PermissionDeniedException();
            }
        }

        $this->removeOldLabels($labelList, $conversationIDs);
        $this->assignLabels($conversationIDs, $labelIDs);

        ClipboardHandler::getInstance()->unmark(
            $conversationIDs,
            ClipboardHandler::getInstance()->getObjectTypeID('com.woltlab.wcf.conversation.conversation')
        );

        return new JsonResponse([]);
    }

    /**
     * @param int[] $conversationIDs
     */
    private function removeOldLabels(ConversationLabelList $labelList, array $conversationIDs): void
    {
        // remove previous labels (if any)
        $labelIDs = [];
        foreach ($labelList as $label) {
            $labelIDs[] = $label->labelID;
        }

        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add("conversationID IN (?)", [$conversationIDs]);
        $conditions->add("labelID IN (?)", [$labelIDs]);

        $sql = "DELETE FROM wcf1_conversation_label_to_object
                " . $conditions;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditions->getParameters());
    }

    /**
     * @param int[] $conversationIDs
     * @param int[] $labelIDs
     */
    private function assignLabels(array $conversationIDs, array $labelIDs): void
    {
        if ($labelIDs === []) {
            return;
        }

        // assign label ids
        $sql = "INSERT INTO wcf1_conversation_label_to_object
                                (labelID, conversationID)
                    VALUES      (?, ?)";
        $statement = WCF::getDB()->prepare($sql);

        WCF::getDB()->beginTransaction();
        foreach ($labelIDs as $labelID) {
            foreach ($conversationIDs as $conversationID) {
                $statement->execute([
                    $labelID,
                    $conversationID,
                ]);
            }
        }
        WCF::getDB()->commitTransaction();
    }
}

/** @internal */
final class AssignConversationLabelsParameters
{
    public function __construct(
        /** @var array<positive-int> * */
        public readonly array $conversationIDs,
        /** @var array<positive-int> * */
        public readonly array $labelIDs
    ) {
    }
}
