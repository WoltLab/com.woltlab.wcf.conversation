<?php

namespace wcf\command\conversation;

use wcf\data\conversation\Conversation;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * Inserts the given users as participants into a conversation and updates the
 * participant count afterwards. Participants that have previously left the
 * conversation rejoin it.
 *
 * This only writes the participant data, use `AddConversationParticipant` to
 * add participants to an existing conversation.
 *
 * @author      Marcel Werk
 * @copyright   2001-2026 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.3
 */
final class InsertConversationParticipants
{
    /**
     * @param int[] $participantIDs
     * @param int[] $invisibleParticipantIDs
     * @param 'all'|'new' $messageVisibility
     */
    public function __construct(
        public readonly Conversation $conversation,
        public readonly array $participantIDs,
        public readonly array $invisibleParticipantIDs = [],
        public readonly string $messageVisibility = 'all',
    ) {}

    public function __invoke(): void
    {
        $usernames = $this->getUsernames();

        if ($this->participantIDs !== []) {
            WCF::getDB()->beginTransaction();
            $sql = "INSERT INTO wcf1_conversation_to_user
                                (conversationID, participantID, username, isInvisible, joinedAt)
                    VALUES      (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY
                    UPDATE      hideConversation = 0,
                                isInvisible = 0,
                                leftAt = 0,
                                lastMessageID = NULL,
                                lastMessageTime = 0,
                                leftByOwnChoice = 1";
            $statement = WCF::getDB()->prepare($sql);

            foreach ($this->participantIDs as $userID) {
                $statement->execute([
                    $this->conversation->conversationID,
                    $userID,
                    $usernames[$userID],
                    0,
                    $this->messageVisibility === 'all' ? 0 : \TIME_NOW,
                ]);
            }
            WCF::getDB()->commitTransaction();
        }

        if ($this->invisibleParticipantIDs !== []) {
            WCF::getDB()->beginTransaction();
            $sql = "INSERT INTO wcf1_conversation_to_user
                                (conversationID, participantID, username, isInvisible)
                    VALUES      (?, ?, ?, ?)";
            $statement = WCF::getDB()->prepare($sql);

            foreach ($this->invisibleParticipantIDs as $userID) {
                $statement->execute([
                    $this->conversation->conversationID,
                    $userID,
                    $usernames[$userID],
                    1,
                ]);
            }
            WCF::getDB()->commitTransaction();
        }

        new UpdateConversationParticipantCounts([$this->conversation->conversationID])();
    }

    /**
     * @return array<int, string>
     */
    private function getUsernames(): array
    {
        $userIDs = \array_merge($this->participantIDs, $this->invisibleParticipantIDs);
        if ($userIDs === []) {
            return [];
        }

        $conditionBuilder = new PreparedStatementConditionBuilder();
        $conditionBuilder->add("userID IN (?)", [$userIDs]);

        $sql = "SELECT  userID, username
                FROM    wcf1_user
                " . $conditionBuilder;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditionBuilder->getParameters());

        return $statement->fetchMap('userID', 'username');
    }
}
