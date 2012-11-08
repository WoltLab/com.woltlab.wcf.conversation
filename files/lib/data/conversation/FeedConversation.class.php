<?php
namespace wcf\data\conversation;
use wcf\data\DatabaseObjectDecorator;
use wcf\data\IFeedEntry;
use wcf\system\request\LinkHandler;

/**
 * Represents a conversation for RSS feeds.
 * 
 * @author 	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation
 * @category	Community Framework
 */
class FeedConversation extends DatabaseObjectDecorator implements IFeedEntry {
	/**
	 * @see	wcf\data\DatabaseObjectDecorator::$baseClass
	 */
	protected static $baseClass = 'wcf\data\conversation\Conversation';
	
	/**
	 * @see	wcf\data\ILinkableDatabaseObject::getLink()
	 */
	public function getLink() {
		return LinkHandler::getInstance()->getLink('Conversation', array('id' => $this->getDecoratedObject()->conversationID));
	}
	
	/**
	 * @see	wcf\data\ITitledDatabaseObject::getTitle()
	 */
	public function getTitle() {
		return $this->getDecoratedObject()->getTitle();
	}
	
	/**
	 * @see	wcf\data\IMessage::getFormattedMessage()
	 */
	public function getFormattedMessage() {
		return '';
	}
	
	/**
	 * @see	wcf\data\IMessage::getMessage()
	 */
	public function getMessage() {
		return '';
	}
	
	/**
	 * @see	wcf\data\IMessage::getExcerpt()
	 */
	public function getExcerpt($maxLength = 255) {
		return '';
	}
	
	/**
	 * @see	wcf\data\IMessage::getUserID()
	 */
	public function getUserID() {
		return $this->getDecoratedObject()->userID;
	}
	
	/**
	 * @see	wcf\data\IMessage::getUsername()
	 */
	public function getUsername() {
		return $this->getDecoratedObject()->username;
	}
	
	/**
	 * @see	wcf\data\IMessage::getTime()
	 */
	public function getTime() {
		return $this->getDecoratedObject()->time;
	}
	
	/**
	 * @see	wcf\data\IMessage::__toString()
	 */
	public function __toString() {
		return $this->getDecoratedObject()->__toString();
	}
}
