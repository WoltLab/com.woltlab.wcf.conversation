<?php

namespace wcf\data\conversation;

use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\label\ConversationLabelList;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * Represents a list of conversations.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @method  ViewableConversation        current()
 * @method  ViewableConversation[]      getObjects()
 * @method  ViewableConversation|null   getSingleObject()
 * @method  ViewableConversation|null   search($objectID)
 * @property    ViewableConversation[] $objects
 */
class UserConversationList extends ConversationList
{
    /**
     * list of available filters
     * @var string[]
     */
    public static $availableFilters = ['hidden', 'draft', 'outbox'];

    /**
     * active filter
     * @var string
     */
    public $filter = '';

    /**
     * label list object
     * @var ConversationLabelList
     */
    public $labelList;

    /**
     * @inheritDoc
     */
    public $decoratorClassName = ViewableConversation::class;

    /**
     * Creates a new UserConversationList
     *
     * @param int $userID
     * @param string $filter
     * @param int $labelID
     */
    public function __construct($userID, $filter = '', $labelID = 0)
    {
        parent::__construct();

        $this->filter = $filter;

        // apply filter
        if ($this->filter === 'draft') {
            $this->getConditionBuilder()->add('conversation.userID = ?', [$userID]);
            $this->getConditionBuilder()->add('conversation.isDraft = 1');
        } else {
            $this->getConditionBuilder()->add('conversation_to_user.participantID = ?', [$userID]);
            $this->getConditionBuilder()
                ->add('conversation_to_user.hideConversation = ?', [$this->filter == 'hidden' ? 1 : 0]);
            $this->sqlConditionJoins = "
                LEFT JOIN   wcf" . WCF_N . "_conversation conversation
                ON          conversation.conversationID = conversation_to_user.conversationID";
            if ($this->filter == 'outbox') {
                $this->getConditionBuilder()->add('conversation.userID = ?', [$userID]);
            }
        }

        // filter by label id
        if ($labelID) {
            $this->getConditionBuilder()->add("conversation.conversationID IN (
                SELECT  conversationID
                FROM    wcf" . WCF_N . "_conversation_label_to_object
                WHERE   labelID = ?
            )", [$labelID]);
        }

        // own posts
        $this->sqlSelects = "DISTINCT conversation_message.userID AS ownPosts";
        $this->sqlJoins = "
            LEFT JOIN   wcf" . WCF_N . "_conversation_message conversation_message
            ON          conversation_message.conversationID = conversation.conversationID
                    AND conversation_message.userID = " . $userID;

        // user info
        if (!empty($this->sqlSelects)) {
            $this->sqlSelects .= ',';
        }
        $this->sqlSelects .= "conversation_to_user.*";
        $this->sqlJoins .= "
            LEFT JOIN   wcf" . WCF_N . "_conversation_to_user conversation_to_user
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

    /**
     * Sets the label list of the user the conversations belong to.
     *
     * @param ConversationLabelList $labelList
     */
    public function setLabelList(ConversationLabelList $labelList)
    {
        $this->labelList = $labelList;
    }

    /**
     * @inheritDoc
     */
    public function countObjects()
    {
        if ($this->filter == 'draft') {
            return parent::countObjects();
        }

        $sql = "SELECT  COUNT(*) AS count
                FROM    wcf" . WCF_N . "_conversation_to_user conversation_to_user
                " . $this->sqlConditionJoins . "
                " . $this->getConditionBuilder();
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($this->getConditionBuilder()->getParameters());
        $row = $statement->fetchArray();

        return $row['count'];
    }

    /**
     * @inheritDoc
     */
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
                FROM    wcf" . WCF_N . "_conversation_to_user conversation_to_user
                    " . $this->sqlConditionJoins . "
                    " . $this->getConditionBuilder() . "
                    " . (!empty($this->sqlOrderBy) ? "ORDER BY " . $this->sqlOrderBy : '');
        $statement = WCF::getDB()->prepareStatement($sql, $this->sqlLimit, $this->sqlOffset);
        $statement->execute($this->getConditionBuilder()->getParameters());
        $this->objectIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @inheritDoc
     */
    public function readObjects()
    {
        if ($this->objectIDs === null) {
            $this->readObjectIDs();
        }

        parent::readObjects();

        if (!empty($this->objects)) {
            $messageIDs = [];
            foreach ($this->objects as $conversation) {
                if ($conversation->lastMessageID) {
                    $messageIDs[] = $conversation->lastMessageID;
                }
            }
            if (!empty($messageIDs)) {
                $conditions = new PreparedStatementConditionBuilder();
                $conditions->add("messageID IN (?)", [$messageIDs]);
                $sql = "SELECT  messageID, userID, username, time
                        FROM    wcf" . WCF_N . "_conversation_message
                        " . $conditions;
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute($conditions->getParameters());
                $messageData = [];
                while ($row = $statement->fetchArray()) {
                    $messageData[$row['messageID']] = $row;
                }

                foreach ($this->objects as $conversation) {
                    if ($conversation->lastMessageID) {
                        $data = (isset($messageData[$conversation->lastMessageID])) ? $messageData[$conversation->lastMessageID] : null;
                        if ($data !== null) {
                            $conversation->setLastMessage($data['userID'], $data['username'], $data['time']);
                        } else {
                            $conversation->setLastMessage(null, '', 0);
                        }
                    }
                }
            }

            $labels = $this->loadLabelAssignments();

            $userIDs = [];
            foreach ($this->objects as $conversationID => $conversation) {
                if (isset($labels[$conversationID])) {
                    foreach ($labels[$conversationID] as $label) {
                        $conversation->assignLabel($label);
                    }
                }

                if ($conversation->userID) {
                    $userIDs[] = $conversation->userID;
                }
                if ($conversation->lastPosterID) {
                    $userIDs[] = $conversation->lastPosterID;
                }
            }

            if (!empty($userIDs)) {
                UserProfileRuntimeCache::getInstance()->cacheObjectIDs($userIDs);
            }
        }
    }

    /**
     * Returns a list of conversation labels.
     *
     * @return  ConversationLabel[]
     */
    protected function getLabels()
    {
        if ($this->labelList === null) {
            $this->labelList = ConversationLabel::getLabelsByUser();
        }

        return $this->labelList->getObjects();
    }

    /**
     * Returns label assignments per conversation.
     *
     * @return  ConversationLabel[][]
     */
    protected function loadLabelAssignments()
    {
        $labels = $this->getLabels();
        if (empty($labels)) {
            return [];
        }

        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add("conversationID IN (?)", [\array_keys($this->objects)]);
        $conditions->add("labelID IN (?)", [\array_keys($labels)]);

        $sql = "SELECT  labelID, conversationID
                FROM    wcf" . WCF_N . "_conversation_label_to_object
                " . $conditions;
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($conditions->getParameters());
        $data = [];
        while ($row = $statement->fetchArray()) {
            if (!isset($data[$row['conversationID']])) {
                $data[$row['conversationID']] = [];
            }

            $data[$row['conversationID']][$row['labelID']] = $labels[$row['labelID']];
        }

        return $data;
    }
}
