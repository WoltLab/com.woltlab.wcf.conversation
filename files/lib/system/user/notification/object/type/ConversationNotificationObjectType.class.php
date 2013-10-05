<?php
namespace wcf\system\user\notification\object\type;

/**
 * Represents a conversation notification object type.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2013 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.user.notification.object.type
 * @category	Community Framework
 */
class ConversationNotificationObjectType extends AbstractUserNotificationObjectType {
	/**
	 * @see	wcf\system\user\notification\object\type\AbstractUserNotificationObjectType::$decoratorClassName
	 */
	protected static $decoratorClassName = 'wcf\system\user\notification\object\ConversationUserNotificationObject';
	
	/**
	 * @see	wcf\system\user\notification\object\type\AbstractUserNotificationObjectType::$objectClassName
	 */
	protected static $objectClassName = 'wcf\data\conversation\Conversation';
	
	/**
	 * @see	wcf\system\user\notification\object\type\AbstractUserNotificationObjectType::$objectListClassName
	 */
	protected static $objectListClassName = 'wcf\data\conversation\ConversationList';
}
