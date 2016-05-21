<?php
namespace wcf\system\user\notification\object\type;

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
	protected static $decoratorClassName = 'wcf\system\user\notification\object\ConversationMessageUserNotificationObject';
	
	/**
	 * @inheritDoc
	 */
	protected static $objectClassName = 'wcf\data\conversation\message\ConversationMessage';
	
	/**
	 * @inheritDoc
	 */
	protected static $objectListClassName = 'wcf\data\conversation\message\ConversationMessageList';
}
