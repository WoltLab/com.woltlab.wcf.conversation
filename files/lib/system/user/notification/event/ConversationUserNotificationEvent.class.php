<?php
namespace wcf\system\user\notification\event;
use wcf\system\user\notification\event\AbstractUserNotificationEvent;
use wcf\system\WCF;

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
		return WCF::getLanguage()->get('wcf.user.notification.conversation.shortOutput');
	}
	
	/**
	 * @see	wcf\system\user\notification\event\IUserNotificationEvent::getMessage()
	 */
	public function getMessage() {
		return WCF::getLanguage()->getDynamicVariable('wcf.user.notification.conversation.output', array(
			'conversation' => $this->userNotificationObject,
		));
	}
	
	/**
	 * @see	wcf\system\user\notification\event\IUserNotificationEvent::getRenderedOutput()
	 */
	public function getRenderedOutput() {
		WCF::getTPL()->assign(array(
			'author' => $this->author,
			'buttons' => $this->getActions(),
			'message' => $this->getMessage(),
			'time' => $this->userNotificationObject->time
		));
		
		return WCF::getTPL()->fetch('userNotificationDetails');
	}
}
