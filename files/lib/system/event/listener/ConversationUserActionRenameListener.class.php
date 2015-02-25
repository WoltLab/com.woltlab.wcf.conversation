<?php
namespace wcf\system\event\listener;
use wcf\system\WCF;

/**
 * Updates the stored username on user rename.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2015 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.event.listener
 * @category	Community Framework
 */
class ConversationUserActionRenameListener implements IParameterizedEventListener {
	/**
	 * @see	\wcf\system\event\listener\IParameterizedEventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		$objects = $eventObj->getObjects();
		$userID = $objects[0]->userID;
		
		$actionParameters = $eventObj->getParameters();
		$username = $actionParameters['data']['username'];
		
		WCF::getDB()->beginTransaction();
		
		// conversations
		$sql = "UPDATE	wcf".WCF_N."_conversation
			SET	username = ?
			WHERE	userID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array($username, $userID));
		
		$sql = "UPDATE	wcf".WCF_N."_conversation
			SET	lastPoster = ?
			WHERE	lastPosterID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array($username, $userID));
		
		// conversation messages
		$sql = "UPDATE	wcf".WCF_N."_conversation_message
			SET	username = ?
			WHERE	userID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array($username, $userID));
		
		WCF::getDB()->commitTransaction();
	}
}
