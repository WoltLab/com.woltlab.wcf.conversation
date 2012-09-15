<?php
namespace wcf\data\conversation\message;
use wcf\data\conversation\Conversation;
use wcf\system\search\SearchResultTextParser;

/**
 * Represents a list of search result.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation.message
 * @category	Community Framework
 */
class SearchResultConversationMessage extends ViewableConversationMessage {
	/**
	 * conversation object
	 * @var	wcf\data\conversation\Conversation
	 */
	public $conversation = null;
	
	/**
	 * Returns the conversation object.
	 * 
	 * @return	wcf\data\conversation\Conversation
	 */
	public function getConversation() {
		if ($this->conversation === null) {
			$this->conversation = new Conversation(null, array(
				'conversationID' => $this->conversationID,
				'subject' => $this->subject
			));
		}
		
		return $this->conversation;
	}
	
	/**
	 * @see	wcf\data\conversation\message\ConversationMessage::getFormattedMessage()
	 */
	public function getFormattedMessage() {
		return SearchResultTextParser::getInstance()->parse($this->getDecoratedObject()->getFormattedMessage());
	}
}
