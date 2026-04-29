<?php

namespace wcf\data\conversation;

use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * Represents a list of conversations in which a specific user is a participant.
 *
 * @author      Marcel Werk
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class UserConversationList extends ConversationList
{
    /**
     * @var string[]
     */
    public static array $availableFilters = ['hidden', 'draft', 'outbox'];

    public function __construct(
        ?int $userID = null,
        public readonly string $filter = '',
        ?int $labelID = null
    ) {
        $userID ??= WCF::getUser()->userID;

        parent::__construct();

        // apply filter
        if ($this->filter === 'draft') {
            $this->getConditionBuilder()->add('conversation.userID = ?', [$userID]);
            $this->getConditionBuilder()->add('conversation.isDraft = 1');
        } else {
            $this->getConditionBuilder()->add('conversation_to_user.participantID = ?', [$userID]);
            $this->getConditionBuilder()
                ->add('conversation_to_user.hideConversation = ?', [$this->filter === 'hidden' ? 1 : 0]);
            $this->sqlConditionJoins = "
                LEFT JOIN   wcf1_conversation conversation
                ON          conversation.conversationID = conversation_to_user.conversationID";
            if ($this->filter === 'outbox') {
                $this->getConditionBuilder()->add('conversation.userID = ?', [$userID]);
            }
        }

        // filter by label id
        if ($labelID !== null) {
            $this->getConditionBuilder()->add("conversation.conversationID IN (
                SELECT  conversationID
                FROM    wcf1_conversation_label_to_object
                WHERE   labelID = ?
            )", [$labelID]);
        }

        // own posts
        $this->sqlSelects = "DISTINCT conversation_message.userID AS ownPosts";
        $this->sqlJoins = "
            LEFT JOIN   wcf1_conversation_message conversation_message
            ON          conversation_message.conversationID = conversation.conversationID
                    AND conversation_message.userID = " . $userID;

        // user info
        $this->sqlSelects .= ", conversation_to_user.*";
        $this->sqlJoins .= "
            LEFT JOIN   wcf1_conversation_to_user conversation_to_user
            ON          conversation_to_user.participantID = " . $userID . "
                    AND conversation_to_user.conversationID = conversation.conversationID";

        if ($this->filter !== 'draft') {
            $this->sqlSelects .= ",
            conversation.*,
            (CASE
                WHEN    conversation_to_user.leftAt <> 0
                        AND conversation.lastPostTime > conversation_to_user.leftAt
                THEN    conversation_to_user.leftAt
                ELSE    conversation.lastPostTime
            END) AS lastPostTime";
            // this avoids appending `conversation.*` to the SELECT list
            $this->useQualifiedShorthand = false;
        }
    }

    #[\Override]
    public function countObjects()
    {
        if ($this->filter === 'draft') {
            return parent::countObjects();
        }

        $sql = "SELECT  COUNT(*) AS count
                FROM    wcf1_conversation_to_user conversation_to_user
                " . $this->sqlConditionJoins . "
                " . $this->getConditionBuilder();
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($this->getConditionBuilder()->getParameters());
        $row = $statement->fetchArray();

        return $row['count'];
    }

    #[\Override]
    public function readObjectIDs()
    {
        if ($this->filter === 'draft') {
            parent::readObjectIDs();

            return;
        }

        $sql = "SELECT  conversation_to_user.conversationID AS objectID,
                        (CASE
                            WHEN    conversation_to_user.leftAt <> 0
                            THEN    conversation_to_user.leftAt
                            ELSE    conversation.lastPostTime
                        END) AS lastPostTime
                FROM    wcf1_conversation_to_user conversation_to_user
                    " . $this->sqlConditionJoins . "
                    " . $this->getConditionBuilder() . "
                    " . ($this->sqlOrderBy !== '' ? "ORDER BY " . $this->sqlOrderBy : '');
        $statement = WCF::getDB()->prepare($sql, $this->sqlLimit, $this->sqlOffset);
        $statement->execute($this->getConditionBuilder()->getParameters());
        $this->objectIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    #[\Override]
    public function readObjects()
    {
        if ($this->objectIDs === null) {
            $this->readObjectIDs();
        }

        parent::readObjects();

        $this->setLastMessages();
    }

    protected function setLastMessages(): void
    {
        if ($this->getObjects() === []) {
            return;
        }

        $messageIDs = [];
        foreach ($this->getObjects() as $conversation) {
            if ($conversation->lastMessageID !== null) {
                $messageIDs[] = $conversation->lastMessageID;
            }
        }

        if ($messageIDs === []) {
            return;
        }


        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add("messageID IN (?)", [$messageIDs]);
        $sql = "SELECT  messageID, userID, username, time
                FROM    wcf1_conversation_message
                        " . $conditions;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditions->getParameters());
        $messageData = [];
        while ($row = $statement->fetchArray()) {
            $messageData[$row['messageID']] = $row;
        }

        foreach ($this->objects as $conversation) {
            if ($conversation->lastMessageID !== null) {
                $data = (isset($messageData[$conversation->lastMessageID])) ? $messageData[$conversation->lastMessageID] : null;
                if ($data !== null) {
                    $conversation->setLastMessage($data['userID'], $data['username'], $data['time']);
                } else {
                    $conversation->setLastMessage(null, '', 0);
                }
            }
        }
    }
}
