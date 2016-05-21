<?php
namespace wcf\system\user\notification\event;
use wcf\system\request\LinkHandler;

/**
 * User notification event for conversation messages.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.user.notification.event
 * @category	Community Framework
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
	
	/**
	 * @inheritDoc
	 */
	public function getEmailMessage($notificationType = 'instant') {
		return $this->getLanguage()->getDynamicVariable('wcf.user.notification.conversation.message.mail', [
			'message' => $this->userNotificationObject,
			'author' => $this->author,
			'notificationType' => $notificationType
		]);
	}
	
	/**
	 * @inheritDoc
	 */
	public function getLink() {
		return LinkHandler::getInstance()->getLink('Conversation', [
			'object' => $this->userNotificationObject->getConversation(),
			'messageID' => $this->userNotificationObject->messageID
		], '#message'.$this->userNotificationObject->messageID);
	}
	
	/**
	 * @inheritDoc
	 */
	public function getEventHash() {
		return sha1($this->eventID . '-' . $this->userNotificationObject->conversationID);
	}
	
	/**
	 * @inheritDoc
	 */
	public function checkAccess() {
		return $this->userNotificationObject->getConversation()->canRead();
	}
}
