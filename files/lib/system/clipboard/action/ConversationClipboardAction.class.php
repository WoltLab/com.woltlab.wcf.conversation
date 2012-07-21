<?php
namespace wcf\system\clipboard\action;
use wcf\system\clipboard\ClipboardEditorItem;
use wcf\system\clipboard\action\IClipboardAction;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\SystemException;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;

/**
 * Prepares clipboard editor items for conversations.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.clipboard.action
 * @category 	Community Framework
 */
class ConversationClipboardAction implements IClipboardAction {
	/**
	 * @see	wcf\system\clipboard\action\IClipboardAction::getTypeName()
	 */
	public function getTypeName() {
		return 'com.woltlab.wcf.conversation.conversation';
	}
	
	/**
	 * @see	wcf\system\clipboard\action\IClipboardAction::execute()
	 */
	public function execute(array $objects, $actionName, $typeData = array()) {
		$item = new ClipboardEditorItem();
		
		// DEBUG ONLY
		if ($actionName == 'label') $actionName = 'assignLabel';
		// DEBUG ONLY
		
		switch ($actionName) {
			case 'assignLabel':
				$conversationIDs = $this->validateAssignLabels($objects);
				if (empty($conversationIDs)) {
					return null;
				}
				
				$item->addParameter('objectIDs', $conversationIDs);
				$item->addParameter('actionName', 'assignLabel');
				$item->addParameter('className', 'wcf\data\conversation\label\ConversationLabelAction');
				$item->setName('conversation.assignLabel');
			break;
			
			default:
				die("implement me: ".$actionName);
			break;
		}
	}
	
	/**
	 * Validates if user may assign labels to given conversations.
	 * 
	 * @return	array<integer>
	 */
	protected function validateAssignLabel(array $conversations) {
		$conversationIDs = $tmpIDs = array();
		
		// check if user has labels
		$sql = "SELECT	COUNT(*) AS count
			FROM	wcf".WCF_N."_conversation_label
			WHERE	userID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(WCF::getUser()->userID));
		$row = $statement->fetchArray();
		if ($row['count'] == 0) {
			return array();
		}
		
		// check if user is the author
		foreach ($conversations as $conversation) {
			if ($conversation->userID == WCF::getUser()->userID) {
				$conversationIDs[] = $conversation->conversationID;
			}
			else {
				$tmpIDs[] = $conversation->conversationID;
			}
		}
		
		// check if user is a participant
		if (!empty($tmpIDs)) {
			$conditions = new PreparedStatementConditionBuilder();
			$conditions->add("conversationID IN (?)", array($tmpIDs));
			$conditions->add("participantID = ?", array(WCF::getUser()->userID));
			
			$sql = "SELECT	conversationID
				FROM	wcf".WCF_N."_conversation_to_user
				".$conditions;
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute($conditions->getParameters());
			while ($row = $statement->fetchArray()) {
				$conversationIDs[] = $row['conversationID'];
				
				// remove id from temporary list
				$index = array_search($row['conversationID'], $tmpIDs);
				unset($tmpIDs[$index]);
			}
			
			if (!empty($tmpIDs)) {
				throw new PermissionDeniedException();
			}
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
