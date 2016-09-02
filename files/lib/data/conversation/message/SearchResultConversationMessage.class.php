<?php
namespace wcf\data\conversation\message;
use wcf\data\conversation\Conversation;
use wcf\data\search\ISearchResultObject;
use wcf\system\request\LinkHandler;
use wcf\system\search\SearchResultTextParser;

/**
 * Represents a list of search result.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\Data\Conversation\Message
 * 
 * @property-read	string|null	$subject
 */
class SearchResultConversationMessage extends ViewableConversationMessage implements ISearchResultObject {
	/**
	 * conversation object
	 * @var	Conversation
	 */
	public $conversation = null;
	
	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * Returns the conversation object.
	 * 
	 * @return	Conversation
	 */
	public function getConversation() {
		if ($this->conversation === null) {
			$this->conversation = new Conversation(null, [
				'conversationID' => $this->conversationID,
				'subject' => $this->subject
			]);
		}
		
		return $this->conversation;
	}
	
	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * @inheritDoc
	 */
	public function getFormattedMessage() {
		return SearchResultTextParser::getInstance()->parse($this->getDecoratedObject()->getSimplifiedFormattedMessage());
	}
	
	/**
	 * @inheritDoc
	 */
	public function getSubject() {
		return $this->subject;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getLink($query = '') {
		if ($query) {
			return LinkHandler::getInstance()->getLink('Conversation', [
				'object' => $this->getConversation(),
				'messageID' => $this->messageID,
				'highlight' => urlencode($query)
			], '#message'.$this->messageID);
		}
		
		return $this->getDecoratedObject()->getLink();
	}
	
	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * @inheritDoc
	 */
	public function getTime() {
		return $this->time;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getObjectTypeName() {
		return 'com.woltlab.wcf.conversation.message';
	}
	
	/**
	 * @inheritDoc
	 */
	public function getContainerTitle() {
		return '';
	}
	
	/**
	 * @inheritDoc
	 */
	public function getContainerLink() {
		return '';
	}
}
