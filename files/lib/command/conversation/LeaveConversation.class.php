<?php

namespace wcf\command\conversation;

use wcf\data\conversation\Conversation;
use wcf\system\log\modification\ConversationModificationLogHandler;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;

/**
 * Removes the active user from the given conversation.
 *
 * @author      Olaf Braun
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 */
final class LeaveConversation
{
    public function __construct(
        public readonly Conversation $conversation,
    ) {}

    public function __invoke(): void
    {
        $sql = "UPDATE  wcf1_conversation_to_user
                SET     hideConversation = ?
                WHERE   conversationID = ?
                    AND participantID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([
            Conversation::STATE_LEFT,
            $this->conversation->conversationID,
            WCF::getUser()->userID,
        ]);

        UserStorageHandler::getInstance()->reset([WCF::getUser()->userID], 'conversationCount');
        UserStorageHandler::getInstance()->reset([WCF::getUser()->userID], 'unreadConversationCount');

        ConversationModificationLogHandler::getInstance()->leave($this->conversation);

        new DeleteEmptyConversations([$this->conversation->conversationID])();
    }
}
