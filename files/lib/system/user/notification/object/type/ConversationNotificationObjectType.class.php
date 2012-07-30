<?php
namespace wcf\system\user\notification\object\type;
use wcf\data\conversation\ConversationList;
use wcf\data\conversation\Conversation;
use wcf\data\object\type\AbstractObjectTypeProcessor;
use wcf\system\user\notification\object\ConversationUserNotificationObject;
use wcf\system\WCF;

/**
 * Represents a conversation notification object type.
 *
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.user.notification.object.type
 * @category 	Community Framework
 */
class ConversationNotificationObjectType extends AbstractObjectTypeProcessor implements IUserNotificationObjectType {
	/**
	 * @see wcf\system\user\notification\object\type\IUserNotificationObjectType::getObjectByID()
	 */
	public function getObjectByID($objectID) {
		$object = new Conversation($objectID);
		if (!$object->conversationID) {
			// create empty object for unknown request id
			$object = new Conversation(null, array('conversationID' => $objectID));
		}
		
		return array($object->conversationID => new ConversationUserNotificationObject($object));
	}

	/**
	 * @see wcf\system\user\notification\object\type\IUserNotificationObjectType::getObjectsByIDs()
	 */
	public function getObjectsByIDs(array $objectIDs) {
		$objectList = new ConversationList();
		$objectList->getConditionBuilder()->add("conversation.conversationID IN (?)", array($objectIDs));
		$objectList->readObjects();
		
		$objects = array();
		foreach ($objectList as $object) {
			$objects[$object->conversationID] = new ConversationUserNotificationObject($object);
		}
		
		foreach ($objectIDs as $objectID) {
			// append empty objects for unknown ids
			if (!isset($objects[$objectID])) {
				$objects[$objectID] = new ConversationUserNotificationObject(new Conversation(null, array('conversationID' => $objectID)));
			}
		}
		
		return $objects;
	}
}
