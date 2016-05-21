<?php
namespace wcf\data\conversation;
use wcf\data\DatabaseObjectDecorator;
use wcf\data\IFeedEntry;
use wcf\system\request\LinkHandler;

/**
 * Represents a conversation for RSS feeds.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2015 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation
 * @category	Community Framework
 */
class FeedConversation extends DatabaseObjectDecorator implements IFeedEntry {
	/**
	 * @see	\wcf\data\DatabaseObjectDecorator::$baseClass
	 */
	protected static $baseClass = 'wcf\data\conversation\Conversation';
	
	/**
	 * @see	\wcf\data\ILinkableObject::getLink()
	 */
	public function getLink() {
		return LinkHandler::getInstance()->getLink('Conversation', array(
			'object' => $this->getDecoratedObject(),
			'appendSession' => false,
			'encodeTitle' => true
		));
	}
	
	/**
	 * @see	\wcf\data\ITitledObject::getTitle()
	 */
	public function getTitle() {
		return $this->getDecoratedObject()->getTitle();
	}
	
	/**
	 * @see	\wcf\data\IMessage::getFormattedMessage()
	 */
	public function getFormattedMessage() {
		return '';
	}
	
	/**
	 * @see	\wcf\data\IMessage::getMessage()
	 */
	public function getMessage() {
		return '';
	}
	
	/**
	 * @see	\wcf\data\IMessage::getExcerpt()
	 */
	public function getExcerpt($maxLength = 255) {
		return '';
	}
	
	/**
	 * @see	\wcf\data\IMessage::getUserID()
	 */
	public function getUserID() {
		return $this->getDecoratedObject()->lastPosterID;
	}
	
	/**
	 * @see	\wcf\data\IMessage::getUsername()
	 */
	public function getUsername() {
		return $this->getDecoratedObject()->lastPoster;
	}
	
	/**
	 * @see	\wcf\data\IMessage::getTime()
	 */
	public function getTime() {
		return $this->getDecoratedObject()->lastPostTime;
	}
	
	/**
	 * @see	\wcf\data\IMessage::__toString()
	 */
	public function __toString() {
		return $this->getFormattedMessage();
	}
	
	/**
	 * @see	\wcf\data\IFeedEntry::getComments()
	 */
	public function getComments() {
		return $this->replies;
	}
	
	/**
	 * @see	\wcf\data\IFeedEntry::getCategories()
	 */
	public function getCategories() {
		return array();
	}
	
	/**
	 * @see	\wcf\data\IMessage::isVisible()
	 */
	public function isVisible() {
		return $this->canRead();
	}
}
