<?php

namespace wcf\data\conversation;

use wcf\data\user\UserProfile;
use wcf\data\user\UserProfileList;
use wcf\system\WCF;

/**
 * Represents a list of conversation participants.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class ConversationParticipantList extends UserProfileList
{
    /**
     * conversation id
     * @var int
     */
    public $conversationID = 0;

    /**
     * @inheritDoc
     */
    public $sqlLimit = 0;

    /**
     * Creates a new ConversationParticipantList object.
     *
     * @param bool $isAuthor true if given user is the author of this conversation
     */
    public function __construct(int $conversationID, int $userID = 0, bool $isAuthor = false)
    {
        parent::__construct();

        $this->conversationID = $conversationID;
        $this->getConditionBuilder()->add('conversation_to_user.conversationID = ?', [$conversationID]);
        if (!$isAuthor) {
            if ($userID) {
                $this->getConditionBuilder()->add(
                    '(conversation_to_user.isInvisible = 0 OR conversation_to_user.participantID = ?)',
                    [$userID]
                );
            } else {
                $this->getConditionBuilder()->add(
                    'conversation_to_user.isInvisible = 0'
                );
            }
        }
        $this->sqlConditionJoins .= "
            LEFT JOIN   wcf1_user user_table
            ON          user_table.userID = conversation_to_user.participantID";

        if (!empty($this->sqlSelects)) {
            $this->sqlSelects .= ',';
        }
        $this->sqlSelects .= 'conversation_to_user.*';
        $this->sqlJoins .= "
            LEFT JOIN   wcf1_conversation_to_user conversation_to_user
            ON          conversation_to_user.participantID = user_table.userID
                    AND conversation_to_user.conversationID = " . $conversationID;
    }

    /**
     * @inheritDoc
     */
    public function countObjects()
    {
        $sql = "SELECT  COUNT(*) AS count
                FROM    wcf1_conversation_to_user conversation_to_user
                " . $this->sqlConditionJoins . "
                " . $this->getConditionBuilder();
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($this->getConditionBuilder()->getParameters());
        $row = $statement->fetchArray();

        return $row['count'];
    }

    /**
     * @inheritDoc
     */
    public function readObjectIDs()
    {
        $this->objectIDs = [];
        $sql = "SELECT  conversation_to_user.participantID AS objectID
                FROM    wcf1_conversation_to_user conversation_to_user
                " . $this->sqlConditionJoins . "
                " . $this->getConditionBuilder() . "
                " . (!empty($this->sqlOrderBy) ? "ORDER BY " . $this->sqlOrderBy : '');
        $statement = WCF::getDB()->prepare($sql, $this->sqlLimit, $this->sqlOffset);
        $statement->execute($this->getConditionBuilder()->getParameters());
        $this->objectIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @inheritDoc
     */
    public function readObjects()
    {
        parent::readObjects();

        // check for deleted users
        $sql = "SELECT  username
                FROM    wcf1_conversation_to_user
                WHERE   conversationID = ?
                    AND participantID IS NULL";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$this->conversationID]);
        $i = 0;
        while ($row = $statement->fetchArray()) {
            // create fake user profiles
            $this->objects['x' . (++$i)] = UserProfile::getGuestUserProfile($row['username']);
            $this->indexToObject[] = 'x' . $i;
        }
    }
}
