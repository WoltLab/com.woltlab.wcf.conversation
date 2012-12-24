<?php
namespace wcf\system\user\notification\event;
use wcf\system\user\notification\event\AbstractUserNotificationEvent;
use wcf\system\WCF;

/**
 * User notification event for conversation messages.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.user.notification.event
 * @category	Community Framework
 */
class ConversationMessageUserNotificationEvent extends AbstractUserNotificationEvent {
	/**
	 * @see	wcf\system\user\notification\event\IUserNotificationEvent::getMessage()
	 */
	public function getTitle() {
		return WCF::getLanguage()->get('wcf.user.notification.conversation.message.shortOutput');
	}
	
	/**
	 * @see	wcf\system\user\notification\event\IUserNotificationEvent::getMessage()
	 */
	public function getMessage() {
		return WCF::getLanguage()->getDynamicVariable('wcf.user.notification.conversation.message.output', array(
			'message' => $this->userNotificationObject,
		));
	}
}
