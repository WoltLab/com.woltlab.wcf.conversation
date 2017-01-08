<?php
namespace wcf\system\user\notification\object;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\DatabaseObjectDecorator;
use wcf\system\request\LinkHandler;

/**
 * Notification object for conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2017 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\User\Notification\Object
 *
 * @method	ConversationMessage	getDecoratedObject()
 * @mixin	ConversationMessage
 */
class ConversationMessageUserNotificationObject extends DatabaseObjectDecorator implements IStackableUserNotificationObject {
	/**
	 * @inheritDoc
	 */
	protected static $baseClass = ConversationMessage::class;
	
	/**
	 * @inheritDoc
	 */
	public function getTitle() {
		return $this->getConversation()->subject;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getURL() {
		return LinkHandler::getInstance()->getLink('Conversation', [
			'object' => $this->getConversation(),
			'messageID' => $this->messageID
		]).'#message'.$this->messageID;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getAuthorID() {
		return $this->userID;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getRelatedObjectID() {
		return $this->conversationID;
	}
}
