<?php

namespace wcf\command\conversation;

use wcf\data\conversation\ConversationBuilder;
use wcf\data\conversation\message\ConversationMessageAction;
use wcf\data\conversation\message\ConversationMessageList;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\log\modification\ConversationModificationLogHandler;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;

/**
 * Deletes the given conversations including their messages.
 *
 * @author      Marcel Werk
 * @copyright   2001-2026 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.3
 */
final class DeleteConversations
{
    /**
     * @param int[] $conversationIDs
     */
    public function __construct(
        public readonly array $conversationIDs,
    ) {}

    public function __invoke(): void
    {
        if ($this->conversationIDs === []) {
            return;
        }

        $conversationIDs = \array_values($this->conversationIDs);

        // The participants are read first to reset their number of unread
        // conversations once the conversations have been deleted.
        $participantIDs = $this->getParticipantIDs($conversationIDs);

        $this->deleteMessages($conversationIDs);

        ConversationBuilder::deleteAll($conversationIDs);

        UserNotificationHandler::getInstance()
            ->removeNotifications('com.woltlab.wcf.conversation.notification', $conversationIDs);

        ConversationModificationLogHandler::getInstance()->deleteLogs($conversationIDs);

        if ($participantIDs !== []) {
            UserStorageHandler::getInstance()->reset($participantIDs, 'unreadConversationCount');
        }
    }

    /**
     * @param non-empty-list<int> $conversationIDs
     * @return list<int>
     */
    private function getParticipantIDs(array $conversationIDs): array
    {
        $conditionBuilder = new PreparedStatementConditionBuilder();
        $conditionBuilder->add("conversationID IN (?)", [$conversationIDs]);
        $conditionBuilder->add("participantID IS NOT NULL");

        $sql = "SELECT  DISTINCT participantID
                FROM    wcf1_conversation_to_user
                " . $conditionBuilder;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditionBuilder->getParameters());

        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @param non-empty-list<int> $conversationIDs
     */
    private function deleteMessages(array $conversationIDs): void
    {
        $messageList = new ConversationMessageList();
        $messageList->getConditionBuilder()->add('conversation_message.conversationID IN (?)', [$conversationIDs]);
        $messageList->readObjectIDs();

        if ($messageList->getObjectIDs() === []) {
            return;
        }

        (new ConversationMessageAction($messageList->getObjectIDs(), 'delete'))->executeAction();
    }
}
