<?php
namespace wcf\data\conversation\message;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationAction;
use wcf\data\conversation\ConversationEditor;
use wcf\data\smiley\SmileyCache;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\DatabaseObject;
use wcf\data\IAttachmentMessageQuickReplyAction;
use wcf\data\IMessageInlineEditorAction;
use wcf\data\IMessageQuoteAction;
use wcf\system\attachment\AttachmentHandler;
use wcf\system\bbcode\BBCodeHandler;
use wcf\system\bbcode\BBCodeParser;
use wcf\system\bbcode\PreParser;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\message\censorship\Censorship;
use wcf\system\message\embedded\object\MessageEmbeddedObjectManager;
use wcf\system\message\quote\MessageQuoteManager;
use wcf\system\message\QuickReplyManager;
use wcf\system\moderation\queue\ModerationQueueManager;
use wcf\system\request\LinkHandler;
use wcf\system\search\SearchIndexManager;
use wcf\system\user\notification\object\ConversationMessageUserNotificationObject;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;
use wcf\util\MessageUtil;
use wcf\util\StringUtil;

/**
 * Executes conversation message-related actions.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2015 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation.message
 * @category	Community Framework
 */
class ConversationMessageAction extends AbstractDatabaseObjectAction implements IAttachmentMessageQuickReplyAction, IMessageInlineEditorAction, IMessageQuoteAction {
	/**
	 * @see	\wcf\data\AbstractDatabaseObjectAction::$className
	 */
	protected $className = 'wcf\data\conversation\message\ConversationMessageEditor';
	
	/**
	 * conversation object
	 * @var	\wcf\data\conversation\Conversation
	 */
	public $conversation = null;
	
	/**
	 * conversation message object
	 * @var	\wcf\data\conversation\message\ConversationMessage
	 */
	public $message = null;
	
	/**
	 * @see	\wcf\data\AbstractDatabaseObjectAction::create()
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
		$messageEditor = new ConversationMessageEditor($message);
		
		// get conversation
		$conversation = (isset($this->parameters['converation']) ? $this->parameters['converation'] : new Conversation($message->conversationID));
		$conversationEditor = new ConversationEditor($conversation);
		
		if (empty($this->parameters['isFirstPost'])) {
			// update last message
			$conversationEditor->addMessage($message);
			
			// fire notification event
			if (!$conversation->isDraft) {
				$notificationRecipients = array_diff($conversation->getParticipantIDs(true), array($message->userID)); // don't notify message author
				if (!empty($notificationRecipients)) {
					UserNotificationHandler::getInstance()->fireEvent('conversationMessage', 'com.woltlab.wcf.conversation.message.notification', new ConversationMessageUserNotificationObject($message), $notificationRecipients);
				}
			}
			
			$userConversation = Conversation::getUserConversation($conversation->conversationID, $message->userID);
			if ($userConversation !== null && $userConversation->isInvisible) {
				// make invisible participant visible
				$sql = "UPDATE	wcf".WCF_N."_conversation_to_user
					SET	isInvisible = 0
					WHERE	participantID = ?
						AND conversationID = ?";
				$statement = WCF::getDB()->prepareStatement($sql);
				$statement->execute(array($message->userID, $conversation->conversationID));
				
				$conversationEditor->updateParticipantSummary();
				$conversationEditor->updateParticipantCount();
			}
			
			// reset visibility if it was hidden but not left
			$sql = "UPDATE	wcf".WCF_N."_conversation_to_user
				SET	hideConversation = ?
				WHERE	conversationID = ?
					AND hideConversation = ?";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute(array(
				Conversation::STATE_DEFAULT,
				$conversation->conversationID,
				Conversation::STATE_HIDDEN
			));
		}
		
		// reset storage
		UserStorageHandler::getInstance()->reset($conversation->getParticipantIDs(), 'unreadConversationCount');
		
		// update search index
		SearchIndexManager::getInstance()->add('com.woltlab.wcf.conversation.message', $message->messageID, $message->message, (!empty($this->parameters['isFirstPost']) ? $conversation->subject : ''), $message->time, $message->userID, $message->username);
		
		// update attachments
		if (isset($this->parameters['attachmentHandler']) && $this->parameters['attachmentHandler'] !== null) {
			$this->parameters['attachmentHandler']->updateObjectID($message->messageID);
		}
		
		// save embedded objects
		if (MessageEmbeddedObjectManager::getInstance()->registerObjects('com.woltlab.wcf.conversation.message', $message->messageID, $message->message)) {
			$messageEditor->update(array(
				'hasEmbeddedObjects' => 1
			));
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
	 * @see	\wcf\data\AbstractDatabaseObjectAction::update()
	 */
	public function update() {
		// count attachments
		if (isset($this->parameters['attachmentHandler']) && $this->parameters['attachmentHandler'] !== null) {
			$this->parameters['data']['attachments'] = count($this->parameters['attachmentHandler']);
		}
		
		parent::update();
		
		// update search index / embedded objects
		if (isset($this->parameters['data']) && isset($this->parameters['data']['message'])) {
			foreach ($this->objects as $message) {
				$conversation = $message->getConversation();
				SearchIndexManager::getInstance()->update('com.woltlab.wcf.conversation.message', $message->messageID, $this->parameters['data']['message'], ($conversation->firstMessageID == $message->messageID ? $conversation->subject : ''), $message->time, $message->userID, $message->username);
				
				if ($message->hasEmbeddedObjects != MessageEmbeddedObjectManager::getInstance()->registerObjects('com.woltlab.wcf.conversation.message', $message->messageID, $this->parameters['data']['message'])) {
					$message->update(array(
						'hasEmbeddedObjects' => ($message->hasEmbeddedObjects ? 0 : 1)
					));
				}
			}
		}
	}
	
	/**
	 * @see	\wcf\data\AbstractDatabaseObjectAction::delete()
	 */
	public function delete() {
		$count = parent::delete();
		
		$attachmentMessageIDs = $conversationIDs = array();
		foreach ($this->objects as $message) {
			if (!in_array($message->conversationID, $conversationIDs)) {
				$conversationIDs[] = $message->conversationID;
			}
			
			if ($message->attachments) {
				$attachmentMessageIDs[] = $message->messageID;
			}
		}
		
		// rebuild conversations
		if (!empty($conversationIDs)) {
			$conversationAction = new ConversationAction($conversationIDs, 'rebuild');
			$conversationAction->executeAction();
		}
		
		if (!empty($this->objectIDs)) {
			// delete notifications
			UserNotificationHandler::getInstance()->removeNotifications('com.woltlab.wcf.conversation.message.notification', $this->objectIDs);
			
			// update search index
			SearchIndexManager::getInstance()->delete('com.woltlab.wcf.conversation.message', $this->objectIDs);
			
			// update embedded objects
			MessageEmbeddedObjectManager::getInstance()->removeObjects('com.woltlab.wcf.conversation.message', $this->objectIDs);

			// remove moderation queues
			ModerationQueueManager::getInstance()->removeQueues('com.woltlab.wcf.conversation.message', $this->objectIDs);
		}
		
		// remove attachments
		if (!empty($attachmentMessageIDs)) {
			AttachmentHandler::removeAttachments('com.woltlab.wcf.conversation.message', $attachmentMessageIDs);
		}
		
		return $count;
	}
	
	/**
	 * @see	\wcf\data\IMessageQuickReply::validateQuickReply()
	 */
	public function validateQuickReply() {
		QuickReplyManager::getInstance()->setAllowedBBCodes(explode(',', WCF::getSession()->getPermission('user.message.allowedBBCodes')));
		QuickReplyManager::getInstance()->validateParameters($this, $this->parameters, 'wcf\data\conversation\Conversation');
	}
	
	/**
	 * @see	\wcf\data\IMessageQuickReply::quickReply()
	 */
	public function quickReply() {
		return QuickReplyManager::getInstance()->createMessage(
			$this,
			$this->parameters,
			'wcf\data\conversation\ConversationAction',
			CONVERSATION_LIST_DEFAULT_SORT_ORDER,
			'conversationMessageList'
		);
	}
	
	/**
	 * @see	\wcf\data\IExtendedMessageQuickReplyAction::validateJumpToExtended()
	 */
	public function validateJumpToExtended() {
		$this->readInteger('containerID');
		$this->readString('message', true);
		$this->readString('tmpHash', true);
		
		$this->conversation = new Conversation($this->parameters['containerID']);
		if (!$this->conversation->conversationID) {
			throw new UserInputException('containerID');
		}
		else if ($this->conversation->isClosed || !Conversation::isParticipant(array($this->conversation->conversationID))) {
			throw new PermissionDeniedException();
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
	 * @see	\wcf\data\IExtendedMessageQuickReplyAction::jumpToExtended()
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
		
		if (!empty($this->parameters['tmpHash'])) {
			QuickReplyManager::getInstance()->setTmpHash($this->parameters['tmpHash']);
		}
		
		// redirect
		return array(
			'url' => $url
		);
	}
	
	/**
	 * @see	\wcf\data\IMessageInlineEditorAction::validateBeginEdit()
	 */
	public function validateBeginEdit() {
		$this->readInteger('containerID');
		$this->readInteger('objectID');
		
		$this->conversation = new Conversation($this->parameters['containerID']);
		if (!$this->conversation->conversationID) {
			throw new UserInputException('containerID');
		}
		
		if ($this->conversation->isClosed || !Conversation::isParticipant(array($this->conversation->conversationID))) {
			throw new PermissionDeniedException();
		}
		
		$this->message = new ConversationMessage($this->parameters['objectID']);
		if (!$this->message->messageID) {
			throw new UserInputException('objectID');
		}
		
		if (!$this->message->canEdit()) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * @see	\wcf\data\IMessageInlineEditorAction::beginEdit()
	 */
	public function beginEdit() {
		BBCodeHandler::getInstance()->setAllowedBBCodes(explode(',', WCF::getSession()->getPermission('user.message.allowedBBCodes')));
		
		WCF::getTPL()->assign(array(
			'defaultSmilies' => SmileyCache::getInstance()->getCategorySmilies(),
			'message' => $this->message,
			'permissionCanUseSmilies' => 'user.message.canUseSmilies',
			'wysiwygSelector' => 'messageEditor'.$this->message->messageID
		));
		
		if (MODULE_ATTACHMENT) {
			$tmpHash = StringUtil::getRandomID();
			$attachmentHandler = new AttachmentHandler('com.woltlab.wcf.conversation.message', $this->message->messageID, $tmpHash);
			$attachmentList = $attachmentHandler->getAttachmentList();
				
			WCF::getTPL()->assign(array(
				'attachmentHandler' => $attachmentHandler,
				'attachmentList' => $attachmentList->getObjects(),
				'attachmentObjectID' => $this->message->messageID,
				'attachmentObjectType' => 'com.woltlab.wcf.conversation.message',
				'attachmentParentObjectID' => 0,
				'tmpHash' => $tmpHash
			));
		}
		
		return array(
			'actionName' => 'beginEdit',
			'template' => WCF::getTPL()->fetch('conversationMessageInlineEditor')
		);
	}
	
	/**
	 * @see	\wcf\data\IMessageInlineEditorAction::validateSave()
	 */
	public function validateSave() {
		$this->readString('message', true, 'data');
		
		if (empty($this->parameters['data']['message'])) {
			throw new UserInputException('message', WCF::getLanguage()->get('wcf.global.form.error.empty'));
		}
		
		$this->validateBeginEdit();
		$this->validateMessage($this->conversation, $this->parameters['data']['message']);
	}
	
	/**
	 * @see	\wcf\data\IMessageInlineEditorAction::save()
	 */
	public function save() {
		$data = array(
			'message' => PreParser::getInstance()->parse(MessageUtil::stripCrap($this->parameters['data']['message']), explode(',', WCF::getSession()->getPermission('user.message.allowedBBCodes')))
		);
		if (!$this->message->getConversation()->isDraft) {
			$data['lastEditTime'] = TIME_NOW;
			$data['editCount'] = $this->message->editCount + 1;
		}
		// execute update action
		$action = new ConversationMessageAction(array($this->message), 'update', array('data' => $data));
		$action->executeAction();
		
		// load new message
		$this->message = new ConversationMessage($this->message->messageID);
		$this->message->getAttachments();
		
		if (MODULE_ATTACHMENT) {
			$attachmentList = $this->message->getAttachments(true);
			$count = 0;
			if ($attachmentList !== null) {
				// set permisions
				$attachmentList->setPermissions(array(
					'canDownload' => true,
					'canViewPreview' => true
				));
				
				$count = count($attachmentList);
			}
			
			// update count to reflect number of attachments after edit
			if ($count != $this->message->attachments) {
				$messageEditor = new ConversationMessageEditor($this->message);
				$messageEditor->update(array('attachments' => $count));
			}
		}
		
		// load embedded objects
		MessageEmbeddedObjectManager::getInstance()->loadObjects('com.woltlab.wcf.conversation.message', array($this->message->messageID));
		
		$data = array(
			'actionName' => 'save',
			'message' => $this->message->getFormattedMessage()
		);
		
		if (MODULE_ATTACHMENT) {
			WCF::getTPL()->assign(array(
				'attachmentList' => $attachmentList,
				'objectID' => $this->message->messageID
			));
			$data['attachmentList'] = WCF::getTPL()->fetch('attachments');
		}
		
		return $data;
	}
	
	/**
	 * @see	\wcf\data\IMessageQuickReply::validateContainer()
	 */
	public function validateContainer(DatabaseObject $conversation) {
		if (!$conversation->conversationID) {
			throw new UserInputException('objectID');
		}
		if ($conversation->isClosed) {
			throw new PermissionDeniedException();
		}
		$conversation->loadUserParticipation();
		if (!$conversation->canRead()) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * @see	\wcf\data\IMessageQuickReplyAction::validateMessage()
	 */
	public function validateMessage(DatabaseObject $container, $message) {
		if (mb_strlen($message) > WCF::getSession()->getPermission('user.conversation.maxLength')) {
			throw new UserInputException('message', WCF::getLanguage()->getDynamicVariable('wcf.message.error.tooLong', array('maxTextLength' => WCF::getSession()->getPermission('user.conversation.maxLength'))));
		}
		
		// search for disallowed bbcodes
		$disallowedBBCodes = BBCodeParser::getInstance()->validateBBCodes($message, explode(',', WCF::getSession()->getPermission('user.message.allowedBBCodes')));
		if (!empty($disallowedBBCodes)) {
			throw new UserInputException('text', WCF::getLanguage()->getDynamicVariable('wcf.message.error.disallowedBBCodes', array('disallowedBBCodes' => $disallowedBBCodes)));
		}
		
		// search for censored words
		if (ENABLE_CENSORSHIP) {
			$result = Censorship::getInstance()->test($message);
			if ($result) {
				throw new UserInputException('message', WCF::getLanguage()->getDynamicVariable('wcf.message.error.censoredWordsFound', array('censoredWords' => $result)));
			}
		}
	}
	
	/**
	 * @see	\wcf\data\IMessageQuickReplyAction::getMessageList()
	 */
	public function getMessageList(DatabaseObject $conversation, $lastMessageTime) {
		$messageList = new ViewableConversationMessageList();
		$messageList->setConversation($conversation);
		$messageList->getConditionBuilder()->add("conversation_message.conversationID = ?", array($conversation->conversationID));
		$messageList->getConditionBuilder()->add("conversation_message.time > ?", array($lastMessageTime));
		$messageList->sqlOrderBy = "conversation_message.time ".CONVERSATION_LIST_DEFAULT_SORT_ORDER;
		$messageList->readObjects();
		
		return $messageList;
	}
	
	/**
	 * @see	\wcf\data\IMessageQuickReply::getPageNo()
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
	 * @see	\wcf\data\IMessageQuickReply::getRedirectUrl()
	 */
	public function getRedirectUrl(DatabaseObject $conversation, DatabaseObject $message) {
		return LinkHandler::getInstance()->getLink('Conversation', array(
			'object' => $conversation,
			'messageID' => $message->messageID
		)).'#message'.$message->messageID;
	}
	
	/**
	 * @see	\wcf\data\IMessageQuoteAction::validateSaveFullQuote()
	 */
	public function validateSaveFullQuote() {
		$this->message = $this->getSingleObject();
		
		if (!Conversation::isParticipant(array($this->message->conversationID))) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * @see	\wcf\data\IMessageQuoteAction::saveFullQuote()
	 */
	public function saveFullQuote() {
		$quoteID = MessageQuoteManager::getInstance()->addQuote(
			'com.woltlab.wcf.conversation.message',
			$this->message->conversationID,
			$this->message->messageID,
			$this->message->getExcerpt(),
			$this->message->getMessage()
		);
		
		if ($quoteID === false) {
			$removeQuoteID = MessageQuoteManager::getInstance()->getQuoteID('com.woltlab.wcf.conversation.message', $this->message->messageID, $this->message->getExcerpt(), $this->message->getMessage());
			MessageQuoteManager::getInstance()->removeQuote($removeQuoteID);
		}
		
		$returnValues = array(
			'count' => MessageQuoteManager::getInstance()->countQuotes(),
			'fullQuoteMessageIDs' => MessageQuoteManager::getInstance()->getFullQuoteObjectIDs(array('com.woltlab.wcf.conversation.message'))
		);
		
		if ($quoteID) {
			$returnValues['renderedQuote'] = MessageQuoteManager::getInstance()->getQuoteComponents($quoteID);
		}
		
		return $returnValues;
	}
	
	/**
	 * @see	\wcf\data\IMessageQuoteAction::validateSaveQuote()
	 */
	public function validateSaveQuote() {
		$this->readString('message');
		$this->readBoolean('renderQuote', true);
		$this->message = $this->getSingleObject();
		
		if (!Conversation::isParticipant(array($this->message->conversationID))) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * @see	\wcf\data\IMessageQuoteAction::saveQuote()
	 */
	public function saveQuote() {
		$quoteID = MessageQuoteManager::getInstance()->addQuote('com.woltlab.wcf.conversation.message', $this->message->conversationID, $this->message->messageID, $this->parameters['message'], false);
		
		$returnValues = array(
			'count' => MessageQuoteManager::getInstance()->countQuotes(),
			'fullQuoteMessageIDs' => MessageQuoteManager::getInstance()->getFullQuoteObjectIDs(array('com.woltlab.wcf.conversation.message'))
		);
		
		if ($this->parameters['renderQuote']) {
			$returnValues['renderedQuote'] = MessageQuoteManager::getInstance()->getQuoteComponents($quoteID);
		}
		
		return $returnValues;
	}
	
	/**
	 * @see	\wcf\data\IMessageQuoteAction::validateGetRenderedQuotes()
	 */
	public function validateGetRenderedQuotes() {
		$this->readInteger('parentObjectID');
		
		$this->conversation = new Conversation($this->parameters['parentObjectID']);
		if (!$this->conversation->conversationID) {
			throw new UserInputException('parentObjectID');
		}
	}
	
	/**
	 * @see	\wcf\data\IMessageQuoteAction::getRenderedQuotes()
	 */
	public function getRenderedQuotes() {
		$quotes = MessageQuoteManager::getInstance()->getQuotesByParentObjectID('com.woltlab.wcf.conversation.message', $this->conversation->conversationID);
		
		return array(
			'template' => implode("\n\n", $quotes)
		);
	}
	
	/**
	 * @see	\wcf\data\IAttachmentMessageQuickReplyAction::getAttachmentHandler()
	 */
	public function getAttachmentHandler(DatabaseObject $conversation) {
		return new AttachmentHandler('com.woltlab.wcf.conversation.message', 0, $this->parameters['tmpHash']);
	}
}
