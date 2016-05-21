<?php
namespace wcf\system\user\notification\object\type;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\message\ConversationMessageList;
use wcf\system\user\notification\object\ConversationMessageUserNotificationObject;

/**
 * Represents a conversation message notification object type.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.user.notification.object.type
 * @category	Community Framework
 */
class ConversationMessageNotificationObjectType extends AbstractUserNotificationObjectType {
	/**
	 * @inheritDoc
	 */
	protected static $decoratorClassName = ConversationMessageUserNotificationObject::class;
	
	/**
	 * @inheritDoc
	 */
	protected static $objectClassName = ConversationMessage::class;
	
	/**
	 * @inheritDoc
	 */
	protected static $objectListClassName = ConversationMessageList::class;
}
