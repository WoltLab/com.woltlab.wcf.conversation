<?php
namespace wcf\data\conversation;
use wcf\data\DatabaseObjectDecorator;
use wcf\data\IFeedEntry;
use wcf\system\request\LinkHandler;

/**
 * Represents a conversation for RSS feeds.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation
 * @category	Community Framework
 */
class FeedConversation extends DatabaseObjectDecorator implements IFeedEntry {
	/**
	 * @inheritDoc
	 */
	protected static $baseClass = 'wcf\data\conversation\Conversation';
	
	/**
	 * @inheritDoc
	 */
	public function getLink() {
		return LinkHandler::getInstance()->getLink('Conversation', [
			'object' => $this->getDecoratedObject(),
			'appendSession' => false,
			'encodeTitle' => true
		]);
	}
	
	/**
	 * @inheritDoc
	 */
	public function getTitle() {
		return $this->getDecoratedObject()->getTitle();
	}
	
	/**
	 * @inheritDoc
	 */
	public function getFormattedMessage() {
		return '';
	}
	
	/**
	 * @inheritDoc
	 */
	public function getMessage() {
		return '';
	}
	
	/**
	 * @inheritDoc
	 */
	public function getExcerpt($maxLength = 255) {
		return '';
	}
	
	/**
	 * @inheritDoc
	 */
	public function getUserID() {
		return $this->getDecoratedObject()->lastPosterID;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getUsername() {
		return $this->getDecoratedObject()->lastPoster;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getTime() {
		return $this->getDecoratedObject()->lastPostTime;
	}
	
	/**
	 * @inheritDoc
	 */
	public function __toString() {
		return $this->getDecoratedObject()->__toString();
	}
	
	/**
	 * @inheritDoc
	 */
	public function getComments() {
		return $this->replies;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getCategories() {
		return [];
	}
	
	/**
	 * @inheritDoc
	 */
	public function isVisible() {
		return $this->canRead();
	}
}
