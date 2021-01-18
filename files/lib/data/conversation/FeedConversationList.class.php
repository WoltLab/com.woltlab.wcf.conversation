<?php

namespace wcf\data\conversation;

use wcf\system\WCF;

/**
 * Represents a list of conversations for RSS feeds.
 *
 * @author  Alexander Ebert
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package WoltLabSuite\Core\Data\Conversation
 *
 * @method  FeedConversation    current()
 * @method  FeedConversation[]  getObjects()
 * @method  FeedConversation|null   search($objectID)
 * @property    FeedConversation[] $objects
 */
class FeedConversationList extends ConversationList
{
    /**
     * @inheritDoc
     */
    public $decoratorClassName = FeedConversation::class;

    /**
     * @inheritDoc
     */
    public $sqlOrderBy = 'conversation.lastPostTime DESC';

    /**
     * @inheritDoc
     */
    public function readObjectIDs()
    {
        $sql = "SELECT	conversation_to_user.conversationID AS objectID
			FROM	wcf" . WCF_N . "_conversation_to_user conversation_to_user
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
    }
}
