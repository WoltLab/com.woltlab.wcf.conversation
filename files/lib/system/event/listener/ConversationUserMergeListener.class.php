<?php
namespace wcf\system\event\listener;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * Merges user conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2015 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.event.listener
 * @category	Community Framework
 */
class ConversationUserMergeListener implements IParameterizedEventListener {
	/**
	 * @see	\wcf\system\event\listener\IParameterizedEventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		// conversation
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("userID IN (?)", array($eventObj->mergedUserIDs));
		$sql = "UPDATE	wcf".WCF_N."_conversation
			SET	userID = ?
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array_merge(array($eventObj->destinationUserID), $conditions->getParameters()));
		
		// conversation_to_user
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("participantID IN (?)", array($eventObj->mergedUserIDs));
		$sql = "UPDATE IGNORE	wcf".WCF_N."_conversation_to_user
			SET		participantID = ?
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array_merge(array($eventObj->destinationUserID), $conditions->getParameters()));
		
		// conversation_message
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("userID IN (?)", array($eventObj->mergedUserIDs));
		$sql = "UPDATE	wcf".WCF_N."_conversation_message
			SET	userID = ?
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array_merge(array($eventObj->destinationUserID), $conditions->getParameters()));
		
		// conversation_label
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("userID IN (?)", array($eventObj->mergedUserIDs));
		$sql = "UPDATE	wcf".WCF_N."_conversation_label
			SET	userID = ?
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array_merge(array($eventObj->destinationUserID), $conditions->getParameters()));
	}
}
