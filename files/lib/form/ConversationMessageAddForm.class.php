<?php
namespace wcf\form;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\message\ConversationMessageAction;
use wcf\data\conversation\message\ViewableConversationMessageList;
use wcf\data\conversation\Conversation;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\message\quote\MessageQuoteManager;
use wcf\system\message\QuickReplyManager;
use wcf\system\page\PageLocationManager;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\HeaderUtil;

/**
 * Shows the conversation reply form.
 * 
 * @author	Marcel Werk
 * @copyright	2009-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	form
 * @category	Community Framework
 */
class ConversationMessageAddForm extends MessageForm {
	/**
	 * @inheritDoc
	 */
	public $enableTracking = true;
	
	/**
	 * @inheritDoc
	 */
	public $attachmentObjectType = 'com.woltlab.wcf.conversation.message';
	
	/**
	 * @inheritDoc
	 */
	public $loginRequired = true;
	
	/**
	 * @inheritDoc
	 */
	public $neededModules = ['MODULE_CONVERSATION'];
	
	/**
	 * @inheritDoc
	 */
	public $neededPermissions = ['user.conversation.canUseConversation'];
	
	/**
	 * conversation id
	 * @var	integer
	 */
	public $conversationID = 0;
	
	/**
	 * conversation
	 * @var	\wcf\data\conversation\Conversation
	 */
	public $conversation = null;
	
	/**
	 * message list
	 * @var	\wcf\data\conversation\message\ConversationMessageList
	 */
	public $messageList = null;
	
	/**
	 * @inheritDoc
	 */
	public function readParameters() {
		parent::readParameters();
		
		if (isset($_REQUEST['id'])) $this->conversationID = intval($_REQUEST['id']);
		$this->conversation = Conversation::getUserConversation($this->conversationID, WCF::getUser()->userID);
		if ($this->conversation === null) {
			throw new IllegalLinkException();
		}
		if (!$this->conversation->canRead() || $this->conversation->isClosed) {
			throw new PermissionDeniedException();
		}
		
		// quotes
		MessageQuoteManager::getInstance()->readParameters();
	}
	
	/**
	 * @inheritDoc
	 */
	public function readFormParameters() {
		parent::readFormParameters();
		
		// quotes
		MessageQuoteManager::getInstance()->readFormParameters();
	}
	
	/**
	 * @inheritDoc
	 */
	protected function validateSubject() {}
	
	/**
	 * @inheritDoc
	 */
	public function readData() {
		parent::readData();
		
		if (empty($_POST)) {
			// check for quick reply message
			$this->text = QuickReplyManager::getInstance()->getMessage('conversation', $this->conversation->conversationID);
			if (empty($this->text)) {
				if (MessageQuoteManager::getInstance()->getQuoteMessageID()) {
					$message = new ConversationMessage(MessageQuoteManager::getInstance()->getQuoteMessageID());
					if (!$message->messageID) {
						throw new IllegalLinkException();
					}
					
					if ($message->conversationID == $this->conversation->conversationID) {
						$message->setConversation($this->conversation);
						$this->text = MessageQuoteManager::getInstance()->renderQuote($message, $message->message);
					}
				}
				
				if (empty($this->text)) {
					// get all message ids from current conversation
					$sql = "SELECT	messageID
						FROM	wcf".WCF_N."_conversation_message
						WHERE	conversationID = ?";
					$statement = WCF::getDB()->prepareStatement($sql);
					$statement->execute([$this->conversation->conversationID]);
					$messageIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);
					
					$renderedQuotes = MessageQuoteManager::getInstance()->getQuotesByObjectIDs('com.woltlab.wcf.conversation.message', $messageIDs);
					if (!empty($renderedQuotes)) {
						$this->text = implode("\n", $renderedQuotes);
					}
				}
			}
		}
		
		// add breadcrumbs
		PageLocationManager::getInstance()->addParentLocation('com.woltlab.wcf.conversation.Conversation', $this->conversation->conversationID, $this->conversation);
		PageLocationManager::getInstance()->addParentLocation('com.woltlab.wcf.conversation.ConversationList');
		
		// get message list
		$this->messageList = new ViewableConversationMessageList();
		$this->messageList->setConversation($this->conversation);
		$this->messageList->sqlLimit = CONVERSATION_REPLY_SHOW_MESSAGES_MAX;
		$this->messageList->sqlOrderBy = 'conversation_message.time DESC';
		$this->messageList->getConditionBuilder()->add('conversation_message.conversationID = ?', [$this->conversation->conversationID]);
		$this->messageList->readObjects();
	}
	
	/**
	 * @inheritDoc
	 */
	public function save() {
		parent::save();
		
		// save message
		$data = array_merge($this->additionalFields, [
			'conversationID' => $this->conversationID,
			'message' => $this->text,
			'time' => TIME_NOW,
			'userID' => WCF::getUser()->userID,
			'username' => WCF::getUser()->username,
			'enableBBCodes' => $this->enableBBCodes,
			'enableHtml' => $this->enableHtml,
			'enableSmilies' => $this->enableSmilies,
			'showSignature' => $this->showSignature
		]);
		
		$messageData = [
			'data' => $data,
			'attachmentHandler' => $this->attachmentHandler
		];
		
		$this->objectAction = new ConversationMessageAction([], 'create', $messageData);
		$resultValues = $this->objectAction->executeAction();
		
		MessageQuoteManager::getInstance()->saved();
		
		$this->saved();
		
		// forward
		HeaderUtil::redirect(LinkHandler::getInstance()->getLink('Conversation', [
			'object' => $this->conversation,
			'messageID' => $resultValues['returnValues']->messageID
			]).'#message'.$resultValues['returnValues']->messageID);
		exit;
	}
	
	/**
	 * @inheritDoc
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		MessageQuoteManager::getInstance()->assignVariables();
		
		WCF::getTPL()->assign([
			'conversation' => $this->conversation,
			'conversationID' => $this->conversationID,
			'items' => $this->messageList->countObjects(),
			'messages' => $this->messageList->getObjects(),
			'attachmentList' => $this->messageList->getAttachmentList()
		]);
	}
	
	/**
	 * @inheritDoc
	 */
	public function getObjectType() {
		return 'com.woltlab.wcf.conversation';
	}
	
	/**
	 * @inheritDoc
	 */
	public function getObjectID() {
		return $this->conversationID;
	}
}
