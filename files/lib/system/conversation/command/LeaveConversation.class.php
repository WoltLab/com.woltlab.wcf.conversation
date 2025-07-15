<?php

namespace wcf\system\conversation\command;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationList;
use wcf\system\log\modification\ConversationModificationLogHandler;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;

/**
 * Command for leaving a conversation.
 *
 * @author      Olaf Braun
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 */
final class LeaveConversation
{
    /**
     * @param int[] $conversationIDs
     */
    public function __construct(
        public readonly array $conversationIDs,
        public readonly int $hideConversation
    ) {
        if (
            !\in_array(
                $this->hideConversation,
                [Conversation::STATE_DEFAULT, Conversation::STATE_HIDDEN, Conversation::STATE_LEFT]
            )
        ) {
            throw new \InvalidArgumentException('Invalid hideConversation value');
        }
    }

    public function __invoke(): void
    {
        $sql = "UPDATE  wcf1_conversation_to_user
                SET     hideConversation = ?
                WHERE   conversationID = ?
                    AND participantID = ?";
        $statement = WCF::getDB()->prepare($sql);

        WCF::getDB()->beginTransaction();
        foreach ($this->conversationIDs as $conversationID) {
            $statement->execute([
                $this->hideConversation,
                $conversationID,
                WCF::getUser()->userID,
            ]);
        }
        WCF::getDB()->commitTransaction();

        if ($this->hideConversation == Conversation::STATE_LEFT) {
            // reset user's conversation counters if user leaves conversation
            // permanently
            UserStorageHandler::getInstance()->reset([WCF::getUser()->userID], 'conversationCount');
            UserStorageHandler::getInstance()->reset([WCF::getUser()->userID], 'unreadConversationCount');

            // add modification log entry
            $conversationList = new ConversationList();
            $conversationList->setObjectIDs($this->conversationIDs);
            $conversationList->readObjects();

            foreach ($conversationList as $conversation) {
                ConversationModificationLogHandler::getInstance()->leave($conversation);
            }
        }

        (new DeleteEmptyConversations($this->conversationIDs))();
    }
}
