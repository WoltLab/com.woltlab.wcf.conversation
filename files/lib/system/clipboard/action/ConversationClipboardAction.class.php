<?php
namespace wcf\system\clipboard\action;
use wcf\data\clipboard\action\ClipboardAction;
use wcf\data\conversation\Conversation;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * Prepares clipboard editor items for conversations.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2015 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.clipboard.action
 * @category	Community Framework
 */
class ConversationClipboardAction extends AbstractClipboardAction {
	/**
	 * @see	\wcf\system\clipboard\action\AbstractClipboardAction::$actionClassActions
	 */
	protected $actionClassActions = array('close', 'markAsRead', 'open');
	
	/**
	 * list of conversations
	 * @var	Conversation[]
	 */
	public $conversations = null;
	
	/**
	 * @see	\wcf\system\clipboard\action\AbstractClipboardAction::$supportedActions
	 */
	protected $supportedActions = array('assignLabel', 'close', 'leave', 'leavePermanently', 'markAsRead', 'open', 'restore');
	
	/**
	 * @see	\wcf\system\clipboard\action\IClipboardAction::execute()
	 */
	public function execute(array $objects, ClipboardAction $action) {
		if ($this->conversations === null) {
			// validate conversations
			$this->validateParticipation($objects);
		}
		
		// check if no conversation was accessible
		if (empty($this->conversations)) {
			return null;
		}
		
		$item = parent::execute($objects, $action);
		
		if ($item === null) {
			return null;
		}
		
		switch ($action->actionName) {
			case 'assignLabel':
				// check if user has labels
				$sql = "SELECT	COUNT(*) AS count
					FROM	wcf".WCF_N."_conversation_label
					WHERE	userID = ?";
				$statement = WCF::getDB()->prepareStatement($sql);
				$statement->execute(array(WCF::getUser()->userID));
				$row = $statement->fetchArray();
				if ($row['count'] == 0) {
					return null;
				}
				
				$item->addParameter('objectIDs', array_keys($this->conversations));
			break;
			
			case 'leave':
				$item->addInternalData('parameters', array('hideConversation' => 1));
				$item->addParameter('actionName', 'hideConversation');
				$item->addParameter('className', $this->getClassName());
			break;
			
			case 'leavePermanently':
				$item->addParameter('objectIDs', array_keys($this->conversations));
				$item->addInternalData('parameters', array('hideConversation' => 2));
				$item->addParameter('actionName', 'hideConversation');
				$item->addParameter('className', $this->getClassName());
			break;
			
			case 'markAsRead':
				$item->addParameter('objectIDs', array_keys($this->conversations));
				$item->addParameter('actionName', 'markAsRead');
				$item->addParameter('className', $this->getClassName());
				$item->addInternalData('confirmMessage', WCF::getLanguage()->getDynamicVariable('wcf.clipboard.item.com.woltlab.wcf.conversation.conversation.markAsRead.confirmMessage', array(
					'count' => $item->getCount()
				)));
			break;
			
			case 'restore':
				$item->addInternalData('parameters', array('hideConversation' => 0));
				$item->addParameter('actionName', 'hideConversation');
				$item->addParameter('className', $this->getClassName());
			break;
		}
		
		return $item;
	}
	
	/**
	 * @see	\wcf\system\clipboard\action\IClipboardAction::getClassName()
	 */
	public function getClassName() {
		return 'wcf\data\conversation\ConversationAction';
	}
	
	/**
	 * @see	\wcf\system\clipboard\action\IClipboardAction::getTypeName()
	 */
	public function getTypeName() {
		return 'com.woltlab.wcf.conversation.conversation';
	}
	
	/**
	 * Returns a list of conversations with user participation.
	 * 
	 * @param	Conversation[]		$conversations
	 * @return	Conversation[]
	 */
	protected function validateParticipation(array $conversations) {
		$conversationIDs = array();
		
		// validate ownership
		foreach ($conversations as $conversation) {
			if ($conversation->userID != WCF::getUser()->userID) {
				$conversationIDs[] = $conversation->conversationID;
			}
		}
		
		// validate participation as non-owner
		if (!empty($conversationIDs)) {
			$conditions = new PreparedStatementConditionBuilder();
			$conditions->add("conversationID IN (?)", array($conversationIDs));
			$conditions->add("participantID = ?", array(WCF::getUser()->userID));
			
			$sql = "SELECT	conversationID
				FROM	wcf".WCF_N."_conversation_to_user
				".$conditions;
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute($conditions->getParameters());
			while ($row = $statement->fetchArray()) {
				$index = array_search($row['conversationID'], $conversationIDs);
				unset($conversationIDs[$index]);
			}
			
			// remove unaccessible conversations
			if (!empty($conversationIDs)) {
				foreach ($conversations as $index => $conversation) {
					if (in_array($conversation->conversationID, $conversationIDs)) {
						unset($conversations[$index]);
					}
				}
			}
		}
		
		foreach ($conversations as $conversation) {
			$this->conversations[$conversation->conversationID] = $conversation;
		}
	}
	
	/**
	 * Validates if user may close the given conversations.
	 * 
	 * @return	integer[]
	 */
	protected function validateClose() {
		$conversationIDs = array();
		
		foreach ($this->conversations as $conversation) {
			if (!$conversation->isClosed && $conversation->userID == WCF::getUser()->userID) {
				$conversationIDs[] = $conversation->conversationID;
			}
		}
		
		return $conversationIDs;
	}
	
	/**
	 * Validates conversations available for leaving.
	 * 
	 * @return	integer[]
	 */
	public function validateLeave() {
		$tmpIDs = array();
		foreach ($this->conversations as $conversation) {
			$tmpIDs[] = $conversation->conversationID;
		}
		
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("conversationID IN (?)", array($tmpIDs));
		$conditions->add("participantID = ?", array(WCF::getUser()->userID));
		$conditions->add("hideConversation <> ?", array(1));
		
		$sql = "SELECT	conversationID
			FROM	wcf".WCF_N."_conversation_to_user
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditions->getParameters());
		
		return $statement->fetchAll(\PDO::FETCH_COLUMN);
	}
	
	/**
	 * Validates conversations applicable for mark as read.
	 * 
	 * @return	integer[]
	 */
	public function validateMarkAsRead() {
		$conversationIDs = array();
		
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("conversationID IN (?)", array(array_keys($this->conversations)));
		$conditions->add("participantID = ?", array(WCF::getUser()->userID));
		
		$sql = "SELECT	conversationID, lastVisitTime
			FROM	wcf".WCF_N."_conversation_to_user
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditions->getParameters());
		$lastVisitTime = array();
		while ($row = $statement->fetchArray()) {
			$lastVisitTime[$row['conversationID']] = $row['lastVisitTime'];
		}
		
		foreach ($this->conversations as $conversation) {
			if (isset($lastVisitTime[$conversation->conversationID]) && $lastVisitTime[$conversation->conversationID] < $conversation->lastPostTime) {
				$conversationIDs[] = $conversation->conversationID;
			}
		}
		
		return $conversationIDs;
	}
	
	/**
	 * Validates if user may open the given conversations.
	 * 
	 * @return	integer[]
	 */
	protected function validateOpen() {
		$conversationIDs = array();
		
		foreach ($this->conversations as $conversation) {
			if ($conversation->isClosed && $conversation->userID == WCF::getUser()->userID) {
				$conversationIDs[] = $conversation->conversationID;
			}
		}
		
		return $conversationIDs;
	}
	
	/**
	 * Validates conversations available for restore.
	 * 
	 * @return	integer[]
	 */
	public function validateRestore() {
		$tmpIDs = array();
		foreach ($this->conversations as $conversation) {
			$tmpIDs[] = $conversation->conversationID;
		}
		
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("conversationID IN (?)", array($tmpIDs));
		$conditions->add("participantID = ?", array(WCF::getUser()->userID));
		$conditions->add("hideConversation <> ?", array(0));
		
		$sql = "SELECT	conversationID
			FROM	wcf".WCF_N."_conversation_to_user
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditions->getParameters());
		
		return $statement->fetchAll(\PDO::FETCH_COLUMN);
	}
}
