<?php
namespace wcf\system\user\notification\event;
use wcf\data\user\UserProfile;
use wcf\system\request\LinkHandler;
use wcf\system\user\notification\object\ConversationUserNotificationObject;

/**
 * User notification event for conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2018 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\User\Notification\Event
 * 
 * @method	ConversationUserNotificationObject	getUserNotificationObject()
 */
class ConversationUserNotificationEvent extends AbstractUserNotificationEvent implements ITestableUserNotificationEvent {
	use TTestableConversationRelatedUserNotificationEvent;
	use TTestableUserNotificationEvent;
	
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
	
	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * @inheritDoc
	 */
	public function getEmailMessage($notificationType = 'instant') {
		return [
			'message-id' => 'com.woltlab.wcf.conversation.notification/'.$this->getUserNotificationObject()->conversationID,
			'template' => 'email_notification_conversation',
			'application' => 'wcf'
		];
	}
	
	/**
	 * @inheritDoc
	 * @since	5.2
	 */
	public function getEmailTitle() {
		return $this->getLanguage()->getDynamicVariable('wcf.user.notification.conversation.mail.title', [
			'author' => $this->author,
			'conversation' => $this->userNotificationObject
		]);
	}
	
	/**
	 * @inheritDoc
	 */
	public function getLink() {
		return LinkHandler::getInstance()->getLink('Conversation', ['object' => $this->userNotificationObject]);
	}
	
	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * @inheritDoc
	 */
	public function checkAccess() {
		return $this->getUserNotificationObject()->canRead();
	}
	
	/**
	 * @inheritDoc
	 */
	public static function getTestObjects(UserProfile $recipient, UserProfile $author) {
		return [new ConversationUserNotificationObject(self::createTestConversation($author, $recipient))];
	}
}
