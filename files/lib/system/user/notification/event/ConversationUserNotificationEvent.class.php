<?php
namespace wcf\system\user\notification\event;
use wcf\system\request\LinkHandler;
use wcf\system\user\notification\object\ConversationUserNotificationObject;

/**
 * User notification event for conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.user.notification.event
 * @category	Community Framework
 * 
 * @method	ConversationUserNotificationObject	getUserNotificationObject()
 */
class ConversationUserNotificationEvent extends AbstractUserNotificationEvent {
	/**
	 * @inheritDoc
	 */
	public function getTitle() {
		return $this->getLanguage()->get('wcf.user.notification.conversation.title');
	}
	
	/**
	 * @inheritDoc
	 */
	public function getMessage() {
		return $this->getLanguage()->getDynamicVariable('wcf.user.notification.conversation.message', [
			'author' => $this->author,
			'conversation' => $this->userNotificationObject
		]);
	}
	
	/**
	 * @inheritDoc
	 */
	public function getEmailMessage($notificationType = 'instant') {
		return $this->getLanguage()->getDynamicVariable('wcf.user.notification.conversation.mail', [
			'conversation' => $this->userNotificationObject,
			'author' => $this->author,
			'notificationType' => $notificationType
		]);
	}
	
	/**
	 * @inheritDoc
	 */
	public function getLink() {
		return LinkHandler::getInstance()->getLink('Conversation', ['object' => $this->userNotificationObject]);
	}
	
	/**
	 * @inheritDoc
	 */
	public function checkAccess() {
		return $this->getUserNotificationObject()->canRead();
	}
}
