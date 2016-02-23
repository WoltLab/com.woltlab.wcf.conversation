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
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("userID IN (?)", array($eventObj->mergedUserIDs));
		$parameters = array_merge(array(
			$eventObj->destinationUserID,
			$eventObj->users[$eventObj->destinationUserID]->username
		), $conditions->getParameters());
		
		// conversation
		$sql = "UPDATE	wcf".WCF_N."_conversation
			SET	userID = ?,
				username = ?
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($parameters);
		
		$participantConditions = new PreparedStatementConditionBuilder();
		$participantConditions->add("lastPosterID IN (?)", array($eventObj->mergedUserIDs));
		$sql = "UPDATE	wcf".WCF_N."_conversation
			SET	lastPosterID = ?,
				lastPoster = ?
			".$participantConditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($parameters); // can still use $parameters, even though $participantConditions != $conditions
		
		// conversation_to_user
		$participantConditions = new PreparedStatementConditionBuilder();
		$participantConditions->add("participantID IN (?)", array($eventObj->mergedUserIDs));
		$sql = "UPDATE IGNORE	wcf".WCF_N."_conversation_to_user
			SET		participantID = ?,
					username = ?
			".$participantConditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($parameters); // can still use $parameters, even though $participantConditions != $conditions
		
		// conversation_message
		$sql = "UPDATE	wcf".WCF_N."_conversation_message
			SET	userID = ?,
				username = ?
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($parameters);
		
		// conversation_label
		$sql = "UPDATE	wcf".WCF_N."_conversation_label
			SET	userID = ?
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array_merge(array($eventObj->destinationUserID), $conditions->getParameters()));
	}
}
