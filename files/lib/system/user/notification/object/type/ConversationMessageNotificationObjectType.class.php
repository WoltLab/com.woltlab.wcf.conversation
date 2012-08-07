<?php
namespace wcf\system\user\notification\object\type;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\message\ConversationMessageList;
use wcf\data\object\type\AbstractObjectTypeProcessor;
use wcf\system\user\notification\object\ConversationMessageUserNotificationObject;

/**
 * Represents a conversation message notification object type.
 *
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.user.notification.object.type
 * @category 	Community Framework
 */
class ConversationMessageNotificationObjectType extends AbstractObjectTypeProcessor implements IUserNotificationObjectType {
	/**
	 * @see wcf\system\user\notification\object\type\IUserNotificationObjectType::getObjectByID()
	 */
	public function getObjectByID($objectID) {
		$object = new ConversationMessage($objectID);
		if (!$object->messageID) {
			// create empty object for unknown request id
			$object = new ConversationMessage(null, array('messageID' => $objectID));
		}
		
		return array($object->messageID => new ConversationMessageUserNotificationObject($object));
	}

	/**
	 * @see wcf\system\user\notification\object\type\IUserNotificationObjectType::getObjectsByIDs()
	 */
	public function getObjectsByIDs(array $objectIDs) {
		$objectList = new ConversationMessageList();
		$objectList->getConditionBuilder()->add("conversation_message.messageID IN (?)", array($objectIDs));
		$objectList->readObjects();
		
		$objects = array();
		foreach ($objectList as $object) {
			$objects[$object->messageID] = new ConversationMessageUserNotificationObject($object);
		}
		
		foreach ($objectIDs as $objectID) {
			// append empty objects for unknown ids
			if (!isset($objects[$objectID])) {
				$objects[$objectID] = new ConversationMessageUserNotificationObject(new ConversationMessage(null, array('messageID' => $objectID)));
			}
		}
		
		return $objects;
	}
}
