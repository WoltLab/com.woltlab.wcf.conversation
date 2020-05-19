<?php
namespace wcf\system\user\notification\object;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\DatabaseObjectDecorator;

/**
 * Notification object for conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2019 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\User\Notification\Object
 *
 * @method	ConversationMessage	getDecoratedObject()
 * @mixin	ConversationMessage
 */
class ConversationMessageUserNotificationObject extends DatabaseObjectDecorator implements IUserNotificationObject {
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
		return $this->getLink();
	}
	
	/**
	 * @inheritDoc
	 */
	public function getAuthorID() {
		return $this->userID;
	}
}
