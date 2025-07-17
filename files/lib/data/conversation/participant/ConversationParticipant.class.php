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
 * @property-read   int     $participantID
 * @property-read   ?int    $participantID
 * @property-read   string  $username
 * @property-read   bool    $hideConversation
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

    public static function getParticipant(int $conversationID, int $userID): ?static
    {
        $sql = "SELECT * FROM wcf1_conversation_to_user WHERE conversationID = ? AND userID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$conversationID, $userID]);

        $row = $statement->fetchSingleRow();
        if ($row !== false) {
            return new static(null, $row);
        }

        return null;
    }
}
