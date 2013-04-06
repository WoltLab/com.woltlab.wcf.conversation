<?php
namespace wcf\system\user\notification\event;
use wcf\system\user\notification\event\AbstractUserNotificationEvent;

/**
 * User notification event for conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.user.notification.event
 * @category	Community Framework
 */
class ConversationUserNotificationEvent extends AbstractUserNotificationEvent {
	/**
	 * @see	wcf\system\user\notification\event\IUserNotificationEvent::getMessage()
	 */
	public function getTitle() {
		return $this->getLanguage()->get('wcf.user.notification.conversation.title');
	}
	
	/**
	 * @see	wcf\system\user\notification\event\IUserNotificationEvent::getMessage()
	 */
	public function getMessage() {
		return $this->getLanguage()->getDynamicVariable('wcf.user.notification.conversation.message', array(
			'conversation' => $this->userNotificationObject,
		));
	}
	
	/**
	 * @see	wcf\system\user\notification\event\IUserNotificationEvent::getEmailMessage()
	 */
	public function getEmailMessage() {
		return $this->getLanguage()->getDynamicVariable('wcf.user.notification.conversation.mail', array(
			'conversation' => $this->userNotificationObject,
			'author' => $this->author
		));
	}
}
