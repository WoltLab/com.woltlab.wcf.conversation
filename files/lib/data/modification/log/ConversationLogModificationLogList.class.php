<?php
namespace wcf\data\modification\log;
use wcf\data\conversation\Conversation;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\log\modification\ConversationModificationLogHandler;
use wcf\system\WCF;

/**
 * Represents a list of modification logs for conversation log page.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2015 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.modification.log
 * @category	Community Framework
 */
class ConversationLogModificationLogList extends ModificationLogList {
	/**
	 * conversation object type id
	 * @var	integer
	 */
	public $conversationObjectTypeID = 0;
	
	/**
	 * conversation object
	 * @var	Conversation
	 */
	public $conversation = null;
	
	/**
	 * @inheritDoc
	 */
	public function __construct() {
		parent::__construct();
		
		$this->conversationObjectTypeID = ConversationModificationLogHandler::getInstance()->getObjectType()->objectTypeID;
	}
	
	/**
	 * Initializes the conversation log modification log list.
	 * 
	 * @param	Conversation	$conversation
	 */
	public function setConversation(Conversation $conversation) {
		$this->conversation = $conversation;
	}
	
	/**
	 * @inheritDoc
	 */
	public function countObjects() {
		$sql = "SELECT	COUNT(modification_log.logID)
			FROM	wcf".WCF_N."_modification_log modification_log
			WHERE	modification_log.objectTypeID = ?
				AND modification_log.objectID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([
			$this->conversationObjectTypeID,
			$this->conversation->conversationID
		]);
		
		return $statement->fetchColumn();
	}
	
	/**
	 * @inheritDoc
	 */
	public function readObjects() {
		$sql = "SELECT	modification_log.*
			FROM	wcf".WCF_N."_modification_log modification_log
			WHERE	modification_log.objectTypeID = ?
				AND modification_log.objectID = ?
			".(!empty($this->sqlOrderBy) ? "ORDER BY ".$this->sqlOrderBy : '');
		$statement = WCF::getDB()->prepareStatement($sql, $this->sqlLimit, $this->sqlOffset);
		$statement->execute([
			$this->conversationObjectTypeID,
			$this->conversation->conversationID
		]);
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
