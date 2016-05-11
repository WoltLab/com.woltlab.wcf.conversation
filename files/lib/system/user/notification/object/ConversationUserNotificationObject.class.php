<?php
namespace wcf\system\user\notification\object;
use wcf\data\conversation\Conversation;
use wcf\data\DatabaseObjectDecorator;
use wcf\system\request\LinkHandler;

/**
 * Notification object for conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.user.notification.object
 * @category	Community Framework
 * 
 * @method	Conversation	getDecoratedObject()
 * @mixin	Conversation
 */
class ConversationUserNotificationObject extends DatabaseObjectDecorator implements IUserNotificationObject {
	/**
	 * @inheritDoc
	 */
	protected static $baseClass = Conversation::class;
	
	/**
	 * @inheritDoc
	 */
	public function getTitle() {
		return $this->subject;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getURL() {
		return LinkHandler::getInstance()->getLink('Conversation', [
			'object' => $this->getDecoratedObject()
		]);
	}
	
	/**
	 * @inheritDoc
	 */
	public function getAuthorID() {
		return $this->userID;
	}
}
