<?php

namespace wcf\command\conversation;

use wcf\data\conversation\Conversation;
use wcf\data\user\User;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;

/**
 *  Marks a conversation for a given user as read.
 *
 * @author      Marcel Werk
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 */
final class MarkConversationAsRead
{
    public function __construct(
        public readonly Conversation $conversation,
        public readonly User $user
    ) {}

    public function __invoke(): void
    {
        $sql = "UPDATE  wcf1_conversation_to_user
                SET     lastVisitTime = ?
                WHERE   participantID = ?
                    AND conversationID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([
            \TIME_NOW,
            $this->user->userID,
            $this->conversation->conversationID,
        ]);

        UserStorageHandler::getInstance()->reset([$this->user->userID], 'unreadConversationCount');

        $this->markNotificationsAsConfirmed($this->conversation->conversationID, $this->user->userID);
    }

    private function markNotificationsAsConfirmed(int $conversationID, int $userID): void
    {
        // 1) Mark notifications about new conversations as read.
        UserNotificationHandler::getInstance()->markAsConfirmed(
            'conversation',
            'com.woltlab.wcf.conversation.notification',
            [$userID],
            [$conversationID]
        );

        // 2) Mark notifications about new replies as read.
        $eventID = UserNotificationHandler::getInstance()
            ->getEvent('com.woltlab.wcf.conversation.message.notification', 'conversationMessage')
            ->eventID;

        $condition = new PreparedStatementConditionBuilder();
        $condition->add('notification.userID = ?', [$userID]);
        $condition->add('notification.confirmTime = ?', [0]);
        $condition->add('notification.eventID = ?', [$eventID]);
        $condition->add("notification.objectID IN (
            SELECT  messageID
            FROM    wcf1_conversation_message
            WHERE   conversationID = ?
                AND time <= ?
        )", [
            $conversationID,
            \TIME_NOW,
        ]);

        $sql = "SELECT  notificationID
                FROM    wcf1_user_notification notification
                {$condition}";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($condition->getParameters());

        UserNotificationHandler::getInstance()->markAsConfirmedByIDs(
            $statement->fetchAll(\PDO::FETCH_COLUMN)
        );
    }
}
