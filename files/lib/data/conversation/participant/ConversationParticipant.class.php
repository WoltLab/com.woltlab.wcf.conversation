<?php

namespace wcf\data\conversation\participant;

use wcf\data\DatabaseObject;
use wcf\system\WCF;

/**
 * Represents a participant of a conversation.
 *
 * @author      Marcel Werk
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 *
 * @property-read   int     $conversationParticipantID
 * @property-read   int     $conversationID
 * @property-read   ?int    $participantID
 * @property-read   string  $username
 * @property-read   0|1|2    $hideConversation
 * @property-read   bool    $isInvisible
 * @property-read   int     $lastVisitTime
 * @property-read   int     $joinedAt
 * @property-read   int     $leftAt
 * @property-read   ?int    $lastMessageID
 * @property-read   bool    $leftByOwnChoice
 */
class ConversationParticipant extends DatabaseObject
{
    /**
     * @inheritDoc
     */
    protected static $databaseTableName = 'conversation_to_user';

    /**
     * @inheritDoc
     */
    protected static $databaseTableIndexName = 'conversationParticipantID';

    /**
     * Returns true if the user has joined the conversation after the provided
     * timestamp.
     */
    public function hasJoinedAfter(int $timestamp): bool
    {
        if ($this->joinedAt === 0) {
            return false;
        }

        return $this->joinedAt > $timestamp;
    }

    /**
     * Returns true if the user has left the conversation before the provided
     * timestamp.
     */
    public function hasLeftBefore(int $timestamp): bool
    {
        if ($this->leftAt === 0) {
            return false;
        }

        return $this->leftAt < $timestamp;
    }

    public static function getParticipant(int $conversationID, int $userID): ?ConversationParticipant
    {
        $sql = "SELECT * FROM wcf1_conversation_to_user WHERE participantID = ? AND conversationID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$userID, $conversationID]);

        $row = $statement->fetchSingleRow();
        if ($row !== false) {
            return new ConversationParticipant(null, $row);
        }

        return null;
    }
}
