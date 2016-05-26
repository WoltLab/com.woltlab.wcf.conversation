<?php
namespace wcf\system\user\notification\object\type;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationList;
use wcf\system\user\notification\object\ConversationUserNotificationObject;
use wcf\system\WCF;

/**
 * Represents a conversation notification object type.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.user.notification.object.type
 * @category	Community Framework
 */
class ConversationNotificationObjectType extends AbstractUserNotificationObjectType {
	/**
	 * @inheritDoc
	 */
	protected static $decoratorClassName = ConversationUserNotificationObject::class;
	
	/**
	 * @inheritDoc
	 */
	protected static $objectClassName = Conversation::class;
	
	/**
	 * @inheritDoc
	 */
	protected static $objectListClassName = ConversationList::class;
	
	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * @inheritDoc
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
