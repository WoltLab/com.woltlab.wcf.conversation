<?php
namespace wcf\system\user\notification\object;
use wcf\data\DatabaseObjectDecorator;
use wcf\system\request\LinkHandler;

/**
 * Notification object for conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2014 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.user.notification.object
 * @category	Community Framework
 */
class ConversationMessageUserNotificationObject extends DatabaseObjectDecorator implements IStackableUserNotificationObject {
	/**
	 * @see	\wcf\data\DatabaseObjectDecorator::$baseClass
	 */
	protected static $baseClass = 'wcf\data\conversation\message\ConversationMessage';
	
	/**
	 * @see	\wcf\system\user\notification\object\IUserNotificationObject::getTitle()
	 */
	public function getTitle() {
		return $this->getConversation()->subject;
	}
	
	/**
	 * @see	\wcf\system\user\notification\object\IUserNotificationObject::getURL()
	 */
	public function getURL() {
		return LinkHandler::getInstance()->getLink('Conversation', array(
			'object' => $this->getConversation(),
			'messageID' => $this->messageID
		)).'#message'.$this->messageID;
	}
	
	/**
	 * @see	\wcf\system\user\notification\object\IUserNotificationObject::getAuthorID()
	 */
	public function getAuthorID() {
		return $this->userID;
	}
	
	/**
	 * @see	\wcf\system\user\notification\object\IStackableUserNotificationObject::getRelatedObjectID()
	 */
	public function getRelatedObjectID() {
		return $this->conversationID;
	}
}
