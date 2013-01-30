<?php
namespace wcf\data\conversation\message;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationEditor;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\DatabaseObject;
use wcf\data\IExtendedMessageQuickReplyAction;
use wcf\data\IMessageInlineEditorAction;
use wcf\data\IMessageQuoteAction;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\message\quote\MessageQuoteManager;
use wcf\system\message\QuickReplyManager;
use wcf\system\request\LinkHandler;
use wcf\system\search\SearchIndexManager;
use wcf\system\user\notification\object\ConversationMessageUserNotificationObject;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Executes message-related actions.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation.message
 * @category	Community Framework
 */
class ConversationMessageAction extends AbstractDatabaseObjectAction implements IExtendedMessageQuickReplyAction, IMessageInlineEditorAction, IMessageQuoteAction {
	/**
	 * @see	wcf\data\AbstractDatabaseObjectAction::$className
	 */
	protected $className = 'wcf\data\conversation\message\ConversationMessageEditor';
	
	/**
	 * conversation object
	 * @var	wcf\data\conversation\Conversation
	 */
	public $conversation = null;
	
	/**
	 * conversation message object
	 * @var	wcf\data\conversation\message\ConversationMessage
	 */
	public $message = null;
	
	/**
	 * @see	wcf\data\AbstractDatabaseObjectAction::create()
	 */
	public function create() {
		// count attachments
		if (isset($this->parameters['attachmentHandler']) && $this->parameters['attachmentHandler'] !== null) {
			$this->parameters['data']['attachments'] = count($this->parameters['attachmentHandler']);
		}
		
		if (LOG_IP_ADDRESS) {
			// add ip address
			if (!isset($this->parameters['data']['ipAddress'])) {
				$this->parameters['data']['ipAddress'] = WCF::getSession()->ipAddress;
			}
		}
		else {
			// do not track ip address
			if (isset($this->parameters['data']['ipAddress'])) {
				unset($this->parameters['data']['ipAddress']);
			}
		}
		
		// create message
		$message = parent::create();
		
		// get conversation
		$conversation = (isset($this->parameters['converation']) ? $this->parameters['converation'] : new Conversation($message->conversationID));
		$conversationEditor = new ConversationEditor($conversation);
		
		if (empty($this->parameters['isFirstPost'])) {
			// update last message
			$conversationEditor->addMessage($message);
			
			// fire notification event
			if (!$conversation->isDraft) {
				UserNotificationHandler::getInstance()->fireEvent('conversationMessage', 'com.woltlab.wcf.conversation.message.notification', new ConversationMessageUserNotificationObject($message), $conversation->getParticipantIDs());
			}
			
			// make invisible participant visible
			$sql = "UPDATE	wcf".WCF_N."_conversation_to_user
				SET	isInvisible = 0
				WHERE	participantID = ?
					AND conversationID = ?";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute(array($message->userID, $conversation->conversationID));
		}
		
		// reset storage
		UserStorageHandler::getInstance()->reset($conversation->getParticipantIDs(), 'unreadConversationCount');
		
		// update search index
		SearchIndexManager::getInstance()->add('com.woltlab.wcf.conversation.message', $message->messageID, $message->message, (!empty($this->parameters['isFirstPost']) ? $conversation->subject : ''), $message->time, $message->userID, $message->username);
		
		// update attachments
		if (isset($this->parameters['attachmentHandler']) && $this->parameters['attachmentHandler'] !== null) {
			$this->parameters['attachmentHandler']->updateObjectID($message->messageID);
		}
		
		// clear quotes
		if (isset($this->parameters['removeQuoteIDs']) && !empty($this->parameters['removeQuoteIDs'])) {
			MessageQuoteManager::getInstance()->markQuotesForRemoval($this->parameters['removeQuoteIDs']);
		}
		MessageQuoteManager::getInstance()->removeMarkedQuotes();
		
		// return new message
		return $message;
	}
	
	/**
	 * @see	wcf\data\AbstractDatabaseObjectAction::update()
	 */
	public function update() {
		// count attachments
		if (isset($this->parameters['attachmentHandler']) && $this->parameters['attachmentHandler'] !== null) {
			$this->parameters['data']['attachments'] = count($this->parameters['attachmentHandler']);
		}
		
		parent::update();
		
		// update search index
		foreach ($this->objects as $message) {
			$conversation = $message->getConversation();
			SearchIndexManager::getInstance()->update('com.woltlab.wcf.conversation.message', $message->messageID, $message->message, ($conversation->firstMessageID == $message->messageID ? $conversation->subject : ''), $message->time, $message->userID, $message->username);
		}
	}
	
	/**
	 * @see	wcf\data\AbstractDatabaseObjectAction::delete()
	 */
	public function delete() {
		$count = parent::delete();
		
		if ($count) {
			$message = reset($this->objects);
			$conversationEditor = new ConversationEditor(new Conversation($message->conversationID));
			
			// reset user storage
			UserStorageHandler::getInstance()->reset($conversationEditor->getParticipantIDs(), 'unreadConversationCount');
			
			// check if last message was deleted
			if (($conversationEditor->replies - $count) == -1) {
				// remove conversation
				$conversationEditor->delete();
			}
			else {
				// check if first message was deleted
				$sql = "SELECT		messageID
					FROM		wcf".WCF_N."_conversation_message
					WHERE		conversationID = ?
					ORDER BY	time DESC";
				$statement = WCF::getDB()->prepareStatement($sql);
				$statement->execute(array($conversationEditor->conversationID));
				$row = $statement->fetchArray();
				
				$data = array('replies' => ($conversationEditor->replies - $count));
				if ($conversationEditor->firstMessageID != $row['messageID']) {
					$data['firstMessageID'] = $row['messageID'];
				}
				
				// update conversation data
				$conversationEditor->update($data);
			}
		}
		
		return $count;
	}
	
	/**
	 * @see	wcf\data\IMessageQuickReply::validateQuickReply()
	 */
	public function validateQuickReply() {
		QuickReplyManager::getInstance()->validateParameters($this, $this->parameters, 'wcf\data\conversation\Conversation');
	}
	
	/**
	 * @see	wcf\data\IMessageQuickReply::quickReply()
	 */
	public function quickReply() {
		return QuickReplyManager::getInstance()->createMessage(
			$this,
			$this->parameters,
			'wcf\data\conversation\ConversationAction',
			'wcf\data\conversation\message\ViewableConversationMessageList',
			'conversationMessageList',
			CONVERSATION_LIST_DEFAULT_SORT_ORDER
		);
	}
	
	/**
	 * @see	wcf\data\IExtendedMessageQuickReplyAction::validateJumpToExtended()
	 */
	public function validateJumpToExtended() {
		if (!isset($this->parameters['message'])) {
			throw new UserInputException('message');
		}
		
		$this->parameters['containerID'] = (isset($this->parameters['containerID'])) ? intval($this->parameters['containerID']) : 0;
		if (!$this->parameters['containerID']) {
			throw new UserInputException('containerID');
		}
		else {
			$this->conversation = new Conversation($this->parameters['containerID']);
			if (!$this->conversation->conversationID) {
				throw new UserInputException('containerID');
			}
			else if ($this->conversation->isClosed || !Conversation::isParticipant(array($this->conversation->conversationID))) {
				throw new PermissionDeniedException();
			}
		}
		
		// editing existing message
		if (isset($this->parameters['messageID'])) {
			$this->message = new ConversationMessage(intval($this->parameters['messageID']));
			if (!$this->message->messageID || ($this->message->conversationID != $this->conversation->conversationID)) {
				throw new UserInputException('messageID');
			}
			
			if (!$this->message->canEdit()) {
				throw new PermissionDeniedException();
			}
		}
	}
	
	/**
	 * @see	wcf\data\IExtendedMessageQuickReplyAction::jumpToExtended()
	 */
	public function jumpToExtended() {
		// quick reply
		if ($this->message === null) {
			QuickReplyManager::getInstance()->setMessage('conversation', $this->conversation->conversationID, $this->parameters['message']);
			$url = LinkHandler::getInstance()->getLink('ConversationMessageAdd', array('id' => $this->conversation->conversationID));
		}
		else {
			// editing message
			QuickReplyManager::getInstance()->setMessage('conversationMessage', $this->message->messageID, $this->parameters['message']);
			$url = LinkHandler::getInstance()->getLink('ConversationMessageEdit', array('id' => $this->message->messageID));
		}
		
		// redirect
		return array(
			'url' => $url
		);
	}
	
	/**
	 * @see	wcf\data\IMessageInlineEditorAction::validateBeginEdit()
	 */
	public function validateBeginEdit() {
		$this->parameters['containerID'] = (isset($this->parameters['containerID'])) ? intval($this->parameters['containerID']) : 0;
		if (!$this->parameters['containerID']) {
			throw new UserInputException('containerID');
		}
		else {
			$this->conversation = new Conversation($this->parameters['containerID']);
			if (!$this->conversation->conversationID) {
				throw new UserInputException('containerID');
			}
			
			if ($this->conversation->isClosed || !Conversation::isParticipant(array($this->conversation->conversationID))) {
				throw new PermissionDeniedException();
			}
		}
		
		$this->parameters['objectID'] = (isset($this->parameters['objectID'])) ? intval($this->parameters['objectID']) : 0;
		if (!$this->parameters['objectID']) {
			throw new UserInputException('objectID');
		}
		else {
			$this->message = new ConversationMessage($this->parameters['objectID']);
			if (!$this->message->messageID) {
				throw new UserInputException('objectID');
			}
			
			if (!$this->message->canEdit()) {
				throw new PermissionDeniedException();
			}
		}
	}
	
	/**
	 * @see	wcf\data\IMessageInlineEditorAction::beginEdit()
	 */
	public function beginEdit() {
		WCF::getTPL()->assign(array(
			'defaultSmilies' => array(), /* TODO: fix this */
			'message' => $this->message,
			'wysiwygSelector' => 'messageEditor'.$this->message->messageID
		));
		
		return array(
			'actionName' => 'beginEdit',
			'template' => WCF::getTPL()->fetch('conversationMessageInlineEditor')
		);
	}
	
	/**
	 * @see	wcf\data\IMessageInlineEditorAction::validateSave()
	 */
	public function validateSave() {
		if (!isset($this->parameters['data']) || !isset($this->parameters['data']['message']) || empty($this->parameters['data']['message'])) {
			throw new UserInputException('message');
		}
		
		$this->validateBeginEdit();
	}
	
	/**
	 * @see	wcf\data\IMessageInlineEditorAction::save()
	 */
	public function save() {
		$messageEditor = new ConversationMessageEditor($this->message);
		$messageEditor->update(array(
			'message' => $this->parameters['data']['message']
		));
		
		// load new message
		$this->message = new ConversationMessage($this->message->messageID);
		$this->message->getAttachments();
		
		return array(
			'actionName' => 'save',
			'message' => $this->message->getFormattedMessage()
		);
	}
	
	/**
	 * @see	wcf\data\IMessageQuickReply::validateContainer()
	 */
	public function validateContainer(DatabaseObject $conversation) {
		if (!$conversation->conversationID) {
			throw new UserInputException('objectID');
		}
		else if ($conversation->isClosed || !Conversation::isParticipant(array($conversation->conversationID))) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * @see	wcf\data\IMessageQuickReplyAction::validateMessage()
	 */
	public function validateMessage(DatabaseObject $container, $message) { }
	
	/**
	 * @see	wcf\data\IMessageQuickReply::getPageNo()
	 */
	public function getPageNo(DatabaseObject $conversation) {
		$sql = "SELECT	COUNT(*) AS count
			FROM	wcf".WCF_N."_conversation_message
			WHERE	conversationID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array($conversation->conversationID));
		$count = $statement->fetchArray();
		
		return array(intval(ceil($count['count'] / CONVERSATION_MESSAGES_PER_PAGE)), $count['count']);
	}
	
	/**
	 * @see	wcf\data\IMessageQuickReply::getRedirectUrl()
	 */
	public function getRedirectUrl(DatabaseObject $conversation, DatabaseObject $message) {
		return LinkHandler::getInstance()->getLink('Conversation', array(
			'object' => $conversation,
			'messageID' => $message->messageID
		)).'#message'.$message->messageID;
	}
	
	/**
	 * @see	wcf\data\IMessageQuoteAction::validateSaveFullQuote()
	 */
	public function validateSaveFullQuote() {
		if (empty($this->objects)) {
			$this->readObjects();
				
			if (empty($this->objects)) {
				throw new UserInputException('objectIDs');
			}
		}
		
		// validate permissions
		$this->message = current($this->objects);
		if (!Conversation::isParticipant(array($this->message->conversationID))) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * @see	wcf\data\IMessageQuoteAction::saveFullQuote()
	 */
	public function saveFullQuote() {
		if (!MessageQuoteManager::getInstance()->addQuote('com.woltlab.wcf.conversation.message', $this->message->messageID, $this->message->getExcerpt(), $this->message->getMessage())) {
			$quoteID = MessageQuoteManager::getInstance()->getQuoteID('com.woltlab.wcf.conversation.message', $this->message->messageID, $this->message->getExcerpt(), $this->message->getMessage());
			MessageQuoteManager::getInstance()->removeQuote($quoteID);
		}
		
		return array(
			'count' => MessageQuoteManager::getInstance()->countQuotes(),
			'fullQuoteMessageIDs' => MessageQuoteManager::getInstance()->getFullQuoteObjectIDs(array('com.woltlab.wcf.conversation.message'))
		);
	}
	
	/**
	 * @see	wcf\data\IMessageQuoteAction::validateSaveQuote()
	 */
	public function validateSaveQuote() {
		$this->parameters['message'] = (isset($this->parameters['message'])) ? StringUtil::trim($this->parameters['message']) : '';
		if (empty($this->parameters['message'])) {
			throw new UserInputException('message');
		}
		
		if (empty($this->objects)) {
			$this->readObjects();
			
			if (empty($this->objects)) {
				throw new UserInputException('objectIDs');
			}
		}
		
		$this->message = current($this->objects);
		if (!Conversation::isParticipant(array($this->message->conversationID))) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * @see	wcf\data\IMessageQuoteAction::saveQuote()
	 */
	public function saveQuote() {
		MessageQuoteManager::getInstance()->addQuote('com.woltlab.wcf.conversation.message', $this->message->messageID, $this->parameters['message']);
		
		return array(
			'count' => MessageQuoteManager::getInstance()->countQuotes(),
			'fullQuoteMessageIDs' => MessageQuoteManager::getInstance()->getFullQuoteObjectIDs(array('com.woltlab.wcf.conversation.message'))
		);
	}
}
