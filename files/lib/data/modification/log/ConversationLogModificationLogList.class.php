<?php
namespace wcf\data\modification\log;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\log\modification\ConversationModificationLogHandler;
use wcf\system\WCF;

/**
 * Represents a list of modification logs for conversation log page.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.modification.log
 * @category	Community Framework
 *
 * @method	ViewableConversationModificationLog		current()
 * @method	ViewableConversationModificationLog[]		getObjects()
 * @method	ViewableConversationModificationLog|null	search($objectID)
 */
class ConversationLogModificationLogList extends ModificationLogList {
	/**
	 * @inheritDoc
	 */
	public function __construct($conversationID) {
		parent::__construct();
		
		// set conditions
		$this->getConditionBuilder()->add('modification_log.objectTypeID = ?', array(ConversationModificationLogHandler::getInstance()->getObjectType('com.woltlab.wcf.conversation.conversation')->objectTypeID));
		$this->getConditionBuilder()->add('modification_log.objectID = ?', array($conversationID));
	}
	
	/**
	 * @inheritDoc
	 */
	public function readObjects() {
		$sql = "SELECT	modification_log.*
			FROM	wcf".WCF_N."_modification_log modification_log
			".$this->getConditionBuilder()."
			".(!empty($this->sqlOrderBy) ? "ORDER BY ".$this->sqlOrderBy : '');
		$statement = WCF::getDB()->prepareStatement($sql, $this->sqlLimit, $this->sqlOffset);
		$statement->execute($this->getConditionBuilder()->getParameters());
		$this->objects = $statement->fetchObjects(($this->objectClassName ?: $this->className));
		
		// use table index as array index
		$objects = $userIDs = [];
		foreach ($this->objects as $object) {
			$objectID = $object->{$this->getDatabaseTableIndexName()};
			$objects[$objectID] = $object;
			
			$this->indexToObject[] = $objectID;
			
			if ($object->userID) {
				$userIDs[] = $object->userID;
			}
		}
		$this->objectIDs = $this->indexToObject;
		$this->objects = $objects;
		
		if (!empty($userIDs)) {
			UserProfileRuntimeCache::getInstance()->cacheObjectIDs($userIDs);
		}
		
		foreach ($this->objects as &$object) {
			$object = new ViewableConversationModificationLog($object);
		}
		unset($object);
	}
	
	/**
	 * Returns all log entries created before given point of time. Applicable entries
	 * will be returned and removed from collection.
	 * 
	 * @param	integer		$time
	 * @return	ViewableConversationModificationLog[]
	 */
	public function getEntriesUntil($time) {
		$entries = [];
		foreach ($this->objects as $index => $entry) {
			if ($entry->time < $time) {
				$entries[] = $entry;
				unset($this->objects[$index]);
			}
		}
		
		if (!empty($entries)) {
			$this->indexToObject = array_keys($this->objects);
		}
		
		return $entries;
	}
}
