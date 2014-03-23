<?php
namespace wcf\data\conversation;
use wcf\system\WCF;

/**
 * Represents a list of conversations for RSS feeds.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2014 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation
 * @category	Community Framework
 */
class FeedConversationList extends ConversationList {
	/**
	 * @see	\wcf\data\DatabaseObjectList::$decoratorClassName
	 */
	public $decoratorClassName = 'wcf\data\conversation\FeedConversation';
	
	/**
	 * @see	\wcf\data\DatabaseObjectList::$sqlOrderBy
	 */
	public $sqlOrderBy = 'conversation.lastPostTime DESC';
	
	/**
	 * @see	\wcf\data\DatabaseObjectList::readObjectIDs()
	 */
	public function readObjectIDs() {
		$this->objectIDs = array();
		$sql = "SELECT	conversation_to_user.conversationID AS objectID
			FROM	wcf".WCF_N."_conversation_to_user conversation_to_user
				".$this->sqlConditionJoins."
				".$this->getConditionBuilder()."
				".(!empty($this->sqlOrderBy) ? "ORDER BY ".$this->sqlOrderBy : '');
		$statement = WCF::getDB()->prepareStatement($sql, $this->sqlLimit, $this->sqlOffset);
		$statement->execute($this->getConditionBuilder()->getParameters());
		while ($row = $statement->fetchArray()) {
			$this->objectIDs[] = $row['objectID'];
		}
	}
	
	/**
	 * @see	\wcf\data\DatabaseObjectList::readObjects()
	 */
	public function readObjects() {
		if ($this->objectIDs === null) {
			$this->readObjectIDs();
		}
		
		parent::readObjects();
	}
}
