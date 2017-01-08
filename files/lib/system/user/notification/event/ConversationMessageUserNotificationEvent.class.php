<?php
namespace wcf\system\user\notification\event;
use wcf\system\email\Email;
use wcf\system\request\LinkHandler;
use wcf\system\user\notification\object\ConversationMessageUserNotificationObject;

/**
 * User notification event for conversation messages.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2017 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\User\Notification\Event
 * 
 * @method	ConversationMessageUserNotificationObject	getUserNotificationObject()
 */
class ConversationMessageUserNotificationEvent extends AbstractUserNotificationEvent {
	/**
	 * @inheritDoc
	 */
	protected $stackable = true;
	
	/**
	 * @inheritDoc
	 */
	public function getTitle() {
		$count = count($this->getAuthors());
		if ($count > 1) {
			return $this->getLanguage()->getDynamicVariable('wcf.user.notification.conversation.message.title.stacked', ['count' => $count]);
		}
		
		return $this->getLanguage()->get('wcf.user.notification.conversation.message.title');
	}
	
	/**
	 * @inheritDoc
	 */
	public function getMessage() {
		$authors = array_values($this->getAuthors());
		$count = count($authors);
		
		if ($count > 1) {
			return $this->getLanguage()->getDynamicVariable('wcf.user.notification.conversation.message.message.stacked', [
				'author' => $this->author,
				'authors' => $authors,
				'count' => $count,
				'message' => $this->userNotificationObject,
				'others' => $count - 1
			]);
		}
		
		return $this->getLanguage()->getDynamicVariable('wcf.user.notification.conversation.message.message', [
			'author' => $this->author,
			'message' => $this->userNotificationObject
		]);
	}
	
	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * @inheritDoc
	 */
	public function getEmailMessage($notificationType = 'instant') {
		$messageID = '<com.woltlab.wcf.conversation.notification/'.$this->getUserNotificationObject()->getConversation()->conversationID.'@'.Email::getHost().'>';
		
		return [
			'template' => 'email_notification_conversationMessage',
			'application' => 'wcf',
			'in-reply-to' => [$messageID],
			'references' => [$messageID]
		];
	}
	
	/**
	 * @inheritDoc
	 */
	public function getLink() {
		return LinkHandler::getInstance()->getLink('Conversation', [
			'object' => $this->getUserNotificationObject()->getConversation(),
			'messageID' => $this->getUserNotificationObject()->messageID
		], '#message'.$this->getUserNotificationObject()->messageID);
	}
	
	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * @inheritDoc
	 */
	public function getEventHash() {
		return sha1($this->eventID . '-' . $this->getUserNotificationObject()->conversationID);
	}
	
	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * @inheritDoc
	 */
	public function checkAccess() {
		return $this->getUserNotificationObject()->getConversation()->canRead();
	}
}
