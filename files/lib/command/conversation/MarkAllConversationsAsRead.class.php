<?php

namespace wcf\command\conversation;

use wcf\data\user\User;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;

/**
 *  Marks all conversations for a given user as read.
 *
 * @author      Marcel Werk
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 */
final class MarkAllConversationsAsRead
{
    public function __construct(
        public readonly User $user
    ) {}

    public function __invoke(): void
    {
        $sql = "UPDATE  wcf1_conversation_to_user
                SET     lastVisitTime = ?
                WHERE   participantID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([
            \TIME_NOW,
            $this->user->userID,
        ]);

        UserStorageHandler::getInstance()->reset([$this->user->userID], 'unreadConversationCount');

        UserNotificationHandler::getInstance()->markAsConfirmed(
            'conversation',
            'com.woltlab.wcf.conversation.notification',
            [$this->user->userID]
        );

        UserNotificationHandler::getInstance()->markAsConfirmed(
            'conversationMessage',
            'com.woltlab.wcf.conversation.message.notification',
            [$this->user->userID]
        );
    }
}
