<?php
namespace wcf\data\conversation\message;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationAction;
use wcf\data\conversation\ConversationEditor;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\DatabaseObject;
use wcf\system\bbcode\MessageParser;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\message\IMessageQuickReply;
use wcf\system\message\QuickReplyManager;
use wcf\system\package\PackageDependencyHandler;
use wcf\system\request\LinkHandler;
use wcf\system\search\SearchIndexManager;
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
 * @category 	Community Framework
 */
class ConversationMessageAction extends AbstractDatabaseObjectAction implements IMessageQuickReply {
	/**
	 * @see wcf\data\AbstractDatabaseObjectAction::$className
	 */
	protected $className = 'wcf\data\conversation\message\ConversationMessageEditor';
	
	/**
	 * conversation object
	 * @var	wcf\data\conversation\Conversation
	 */
	public $conversation = null;
	
	/**
	 * @see wcf\data\AbstractDatabaseObjectAction::create()
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
		
		// get thread
		$converation = (isset($this->parameters['converation']) ? $this->parameters['converation'] : new Conversation($message->conversationID));
		$conversationEditor = new ConversationEditor($converation);

		if (empty($this->parameters['isFirstPost'])) {
			// update last message
			$conversationEditor->addMessage($message);
		}
		
		// reset storage
		UserStorageHandler::getInstance()->reset($converation->getParticipantIDs(), 'unreadConversationCount', PackageDependencyHandler::getInstance()->getPackageID('com.woltlab.wcf.conversation'));
		
		// update search index
		SearchIndexManager::getInstance()->add('com.woltlab.wcf.conversation.message', $message->messageID, $message->message, (!empty($this->parameters['isFirstPost']) ? $converation->subject : ''), $message->time, $message->userID, $message->username);
		
		// update attachments
		if (isset($this->parameters['attachmentHandler']) && $this->parameters['attachmentHandler'] !== null) {
			$this->parameters['attachmentHandler']->updateObjectID($message->messageID);
		}
		
		// return new message
		return $message;
	}
	
	/**
	 * @see wcf\data\AbstractDatabaseObjectAction::update()
	 */
	public function update() {
		// count attachments
		if (isset($this->parameters['attachmentHandler']) && $this->parameters['attachmentHandler'] !== null) {
			$this->parameters['data']['attachments'] = count($this->parameters['attachmentHandler']);
		}
		
		parent::update();
		
		// @todo: update search index
	}
	
	/**
	 * @see	wcf\system\message\IMessageQuickReply::validateQuickReply()
	 */
	public function validateQuickReply() {
		QuickReplyManager::getInstance()->validateParameters($this, $this->parameters, 'wcf\data\conversation\Conversation');
	}
	
	/**
	 * @see	wcf\system\message\IMessageQuickReply::quickReply()
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
	 * @see	wcf\system\message\IMessageQuickReply::validateContainer()
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
	 * @see	wcf\system\message\IMessageQuickReply::getPageNo()
	 */
	public function getPageNo(DatabaseObject $conversation) {
		$sql = "SELECT	COUNT(*) AS count
			FROM	wcf".WCF_N."_conversation_message
			WHERE	conversationID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array($conversation->conversationID));
		$count = $statement->fetchArray();
		
		return array(intval(ceil($count['count'] / CONVERSATIONS_PER_PAGE)), $count['count']);
	}
	
	/**
	 * @see	wcf\system\message\IMessageQuickReply::getRedirectUrl()
	 */
	public function getRedirectUrl(DatabaseObject $conversation, DatabaseObject $message) {
		return LinkHandler::getInstance()->getLink('Conversation', array(
			'object' => $conversation,
			'messageID' => $message->messageID
		)).'#message'.$message->messageID;
	}
}
