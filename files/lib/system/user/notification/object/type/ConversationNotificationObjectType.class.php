<?php
namespace wcf\system\user\notification\object\type;
use wcf\data\conversation\Conversation;
use wcf\system\WCF;

/**
 * Represents a conversation notification object type.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2015 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.user.notification.object.type
 * @category	Community Framework
 */
class ConversationNotificationObjectType extends AbstractUserNotificationObjectType {
	/**
	 * @see	\wcf\system\user\notification\object\type\AbstractUserNotificationObjectType::$decoratorClassName
	 */
	protected static $decoratorClassName = 'wcf\system\user\notification\object\ConversationUserNotificationObject';
	
	/**
	 * @see	\wcf\system\user\notification\object\type\AbstractUserNotificationObjectType::$objectClassName
	 */
	protected static $objectClassName = 'wcf\data\conversation\Conversation';
	
	/**
	 * @see	\wcf\system\user\notification\object\type\AbstractUserNotificationObjectType::$objectListClassName
	 */
	protected static $objectListClassName = 'wcf\data\conversation\ConversationList';
	
	/**
	 * @see	\wcf\system\user\notification\object\type\IUserNotificationObjectType::getObjectsByIDs()
	 */
	public function getObjectsByIDs(array $objectIDs) {
		$objects = Conversation::getUserConversations($objectIDs, WCF::getUser()->userID);
		
		foreach ($objects as $objectID => $conversation) {
			$objects[$objectID] = new static::$decoratorClassName($conversation);
		}
		
		foreach ($objectIDs as $objectID) {
			// append empty objects for unknown ids
			if (!isset($objects[$objectID])) {
				// '__unknownNotificationObject' tells the notification API
				// that the object does not exist anymore so that the related
				// notification can be deleted automatically
				$objects[$objectID] = new static::$decoratorClassName(new static::$objectClassName(null, [
					'__unknownNotificationObject' => true,
					'conversationID' => $objectID
				]));
			}
		}
		
		return $objects;
	}
}
