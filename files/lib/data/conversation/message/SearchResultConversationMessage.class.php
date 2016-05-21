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
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation.message
 * @category	Community Framework
 */
class SearchResultConversationMessage extends ViewableConversationMessage implements ISearchResultObject {
	/**
	 * conversation object
	 * @var	\wcf\data\conversation\Conversation
	 */
	public $conversation = null;
	
	/**
	 * Returns the conversation object.
	 * 
	 * @return	\wcf\data\conversation\Conversation
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
	
	/**
	 * @see	\wcf\data\conversation\message\ConversationMessage::getFormattedMessage()
	 */
	public function getFormattedMessage() {
		return SearchResultTextParser::getInstance()->parse($this->getDecoratedObject()->getSimplifiedFormattedMessage());
	}
	
	/**
	 * @see	\wcf\data\search\ISearchResultObject::getSubject()
	 */
	public function getSubject() {
		return $this->subject;
	}
	
	/**
	 * @see	\wcf\data\search\ISearchResultObject::getLink()
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
	
	/**
	 * @see	\wcf\data\search\ISearchResultObject::getTime()
	 */
	public function getTime() {
		return $this->time;
	}
	
	/**
	 * @see	\wcf\data\search\ISearchResultObject::getObjectTypeName()
	 */
	public function getObjectTypeName() {
		return 'com.woltlab.wcf.conversation.message';
	}
	
	/**
	 * @see	\wcf\data\search\ISearchResultObject::getContainerTitle()
	 */
	public function getContainerTitle() {
		return '';
	}
	
	/**
	 * @see	\wcf\data\search\ISearchResultObject::getContainerLink()
	 */
	public function getContainerLink() {
		return '';
	}
}
