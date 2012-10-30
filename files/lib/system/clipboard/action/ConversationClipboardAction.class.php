<?php
namespace wcf\system\clipboard\action;
use wcf\system\clipboard\ClipboardEditorItem;
use wcf\system\clipboard\action\IClipboardAction;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\SystemException;
use wcf\system\WCF;

/**
 * Prepares clipboard editor items for conversations.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.clipboard.action
 * @category	Community Framework
 */
class ConversationClipboardAction implements IClipboardAction {
	/**
	 * list of conversations
	 * @var	array<wcf\data\conversation\Conversation>
	 */
	public $conversations = null;
	
	/**
	 * @see	wcf\system\clipboard\action\IClipboardAction::getTypeName()
	 */
	public function getTypeName() {
		return 'com.woltlab.wcf.conversation.conversation';
	}
	
	/**
	 * @see	wcf\system\clipboard\action\IClipboardAction::execute()
	 */
	public function execute(array $objects, $actionName, array $typeData = array()) {
		if ($this->conversations === null) {
			// validate conversations
			$this->validateParticipation($objects);
		}
		
		// check if no conversation was accessible
		if (empty($this->conversations)) {
			return null;
		}
		
		$item = new ClipboardEditorItem();
		
		switch ($actionName) {
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
				$item->setName('conversation.assignLabel');
			break;
			
			case 'close':
				$conversationIDs = $this->validateClose();
				if (empty($conversationIDs)) {
					return null;
				}
				
				$item->addParameter('objectIDs', $conversationIDs);
				$item->addParameter('actionName', 'close');
				$item->addParameter('className', 'wcf\data\conversation\ConversationAction');
				$item->setName('conversation.close');
			break;
			
			case 'leave':
				$conversationIDs = $this->validateLeave();
				if (empty($conversationIDs)) {
					return null;
				}
				
				$item->addInternalData('parameters', array('hideConversation' => 1));
				$item->addParameter('objectIDs', $conversationIDs);
				$item->addParameter('actionName', 'hideConversation');
				$item->addParameter('className', 'wcf\data\conversation\ConversationAction');
				$item->setName('conversation.leave');
			break;
			
			case 'leavePermanently':
				$item->addInternalData('parameters', array('hideConversation' => 2));
				$item->addParameter('objectIDs', array_keys($this->conversations));
				$item->addParameter('actionName', 'hideConversation');
				$item->addParameter('className', 'wcf\data\conversation\ConversationAction');
				$item->setName('conversation.leavePermanently');
			break;
			
			case 'open':
				$conversationIDs = $this->validateOpen();
				if (empty($conversationIDs)) {
					return null;
				}
				
				$item->addParameter('objectIDs', $conversationIDs);
				$item->addParameter('actionName', 'open');
				$item->addParameter('className', 'wcf\data\conversation\ConversationAction');
				$item->setName('conversation.open');
			break;
			
			case 'restore':
				$conversationIDs = $this->validateRestore();
				if (empty($conversationIDs)) {
					return null;
				}
				
				$item->addInternalData('parameters', array('hideConversation' => 0));
				$item->addParameter('objectIDs', array_keys($this->conversations));
				$item->addParameter('actionName', 'hideConversation');
				$item->addParameter('className', 'wcf\data\conversation\ConversationAction');
				$item->setName('conversation.restore');
			break;
			
			default:
				throw new SystemException("Unknown action '".$actionName."'");
			break;
		}
		
		return $item;
	}
	
	/**
	 * @see	wcf\system\clipboard\action\IClipboardAction::getClassName()
	 */
	public function getClassName() {
		return 'wcf\data\conversation\ConversationAction';
	}
	
	/**
	 * Returns a list of conversations with user participation.
	 * 
	 * @param	array<wcf\data\conversation\Conversation>
	 * @return	array<wcf\data\conversation\Conversation>
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
			$conditions->add("userID = ?", array(WCF::getUser()->userID));
			
			$sql = "SELECT	conversationID
				FROM	wcf".WCF_N."_conversation_to_user
				".$conditions;
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute($conditions->getParameters());
			while ($row = $statement->fetchArray()) {
				$index = array_search($row['conversationID'], $conversationIDs);
				unset($index);
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
	 * @return	array<integer>
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
	 * @return	array<integer>
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
		
		$conversationIDs = array();
		while ($row = $statement->fetchArray()) {
			$conversationIDs[] = $row['conversationID'];
		}
		
		return $conversationIDs;
	}
	
	/**
	 * Validates if user may open the given conversations.
	 *
	 * @return	array<integer>
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
	 * @return	array<integer>
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
		
		$conversationIDs = array();
		while ($row = $statement->fetchArray()) {
			$conversationIDs[] = $row['conversationID'];
		}
		
		return $conversationIDs;
	}
	
	/**
	 * @see	wcf\system\clipboard\action\IClipboardAction::getEditorLabel()
	 */
	public function getEditorLabel(array $objects) {
		return WCF::getLanguage()->getDynamicVariable('wcf.clipboard.label.conversation.marked', array('count' => count($objects)));
	}
}
