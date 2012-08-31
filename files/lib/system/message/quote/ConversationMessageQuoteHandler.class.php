<?php
namespace wcf\system\message\quote;
use wcf\data\conversation\message\ConversationMessageList;
use wcf\data\conversation\ConversationList;

/**
 * IMessageQuoteHandler implementation for conversation messages.
 * 
 * @author 	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.message.quote
 * @category 	Community Framework
 */
class ConversationMessageQuoteHandler extends AbstractMessageQuoteHandler {
	/**
	 * @see	wcf\system\message\quote\AbstractMessageQuoteHandler::getMessages()
	 */
	protected function getMessages(array $data) {
		// read messages
		$messageList = new ConversationMessageList();
		$messageList->getConditionBuilder()->add("conversation_message.messageID IN (?)", array(array_keys($data)));
		$messageList->sqlLimit = 0;
		$messageList->readObjects();
		$messages = $messageList->getObjects();
		
		// read conversations
		$conversationIDs = array();
		foreach ($messages as $message) {
			$conversationIDs[] = $message->conversationID;
		}
		
		$conversationList = new ConversationList();
		$conversationList->getConditionBuilder()->add("conversation.conversationID IN (?)", array($conversationIDs));
		$conversationList->sqlLimit = 0;
		$conversationList->readObjects();
		$conversations = $conversationList->getObjects();
		
		// create QuotedMessage objects
		$quotedMessages = array();
		foreach ($messages as $conversationMessage) {
			$conversationMessage->setConversation($conversations[$conversationMessage->conversationID]);
			$message = new QuotedMessage($conversationMessage);
			
			foreach ($data[$conversationMessage->messageID] as $quoteID) {
				$message->addQuote($quoteID, MessageQuoteManager::getInstance()->getQuote($quoteID));
			}
			
			$quotedMessages[] = $message;
		}
		
		return $quotedMessages;
	}
}
