<?php

namespace wcf\command\conversation;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationBuilder;
use wcf\data\conversation\participant\ConversationParticipant;
use wcf\data\conversation\participant\ConversationParticipantBuilder;
use wcf\system\log\modification\ConversationModificationLogHandler;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;

/**
 * Removes a participant from a conversation.
 *
 * @author      Olaf Braun
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 */
final class RemoveConversationParticipant
{
    public function __construct(
        public readonly Conversation $conversation,
        public readonly int $participantID,
    ) {}

    public function __invoke(): void
    {
        $this->removeParticipant();

        $participant = $this->conversation->getOtherParticipant($this->participantID);
        if ($participant !== null && $participant->isInvisible === 0) {
            ConversationModificationLogHandler::getInstance()->removeParticipant($this->conversation, $this->participantID);
        }

        UserStorageHandler::getInstance()->reset([$this->participantID], 'unreadConversationCount');
    }

    private function removeParticipant(): void
    {
        $participant = ConversationParticipant::getParticipant(
            $this->conversation->conversationID,
            $this->participantID
        );
        if ($participant === null) {
            return;
        }

        $sql = "SELECT      messageID, time
                FROM        wcf1_conversation_message
                WHERE       conversationID = ?
                        AND time >= ?
                        AND time <= ?
                ORDER BY    time DESC";
        $statement = WCF::getDB()->prepare($sql, 1);
        $statement->execute([
            $this->conversation->conversationID,
            $participant->joinedAt,
            \TIME_NOW,
        ]);
        $lastMessage = $statement->fetchSingleRow();

        ConversationParticipantBuilder::forUpdate($participant)
            ->setLeftAt(\TIME_NOW)
            ->setLastMessageID($lastMessage !== false ? $lastMessage['messageID'] : null)
            ->setLastMessageTime($lastMessage !== false ? $lastMessage['time'] : 0)
            ->setLeftByOwnChoice(false)
            ->update();

        // The author and invisible participants are not included in the count.
        if ($this->participantID !== $this->conversation->userID && $participant->isInvisible === 0) {
            ConversationBuilder::forUpdate($this->conversation)
                ->incrementParticipants(-1)
                ->update();
        }
    }
}
