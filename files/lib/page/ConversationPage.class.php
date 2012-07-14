<?php
namespace wcf\page;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\ConversationAction;
use wcf\data\conversation\Conversation;
use wcf\system\breadcrumb\Breadcrumb;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\HeaderUtil;

/**
 * Shows a conversation.
 *
 * @author	Marcel Werk
 * @copyright	2009-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	page
 * @category 	Community Framework
 */
class ConversationPage extends MultipleLinkPage {
	/**
	 * @see wcf\page\MultipleLinkPage::$itemsPerPage
	 */
	public $itemsPerPage = CONVERSATION_MESSAGES_PER_PAGE;
	
	/**
	 * @see wcf\page\MultipleLinkPage::$sortOrder
	 */
	public $sortOrder = 'ASC';
	
	/**
	 * @see	wcf\page\MultipleLinkPage::$objectListClassName
	 */
	public $objectListClassName = 'wcf\data\conversation\message\ViewableConversationMessageList';
	
	/**
	 * conversation id
	 * @var integer
	 */
	public $conversationID = 0;
	
	/**
	 * conversation object
	 * @var wcf\data\conversation\Conversation
	 */
	public $conversation = null;
	
	/**
	 * message id
	 * @var integer
	 */
	public $messageID = 0;
	
	/**
	 * conversation message object
	 * @var wcf\data\conversation\message\ConversationMessage
	 */
	public $message = null;
	
	/**
	 * sidebar factory object
	 * @var	wcf\system\message\sidebar\MessageSidebarFactory
	 */
	public $sidebarFactory = null;
	
	/**
	 * @see wcf\page\IPage::readParameters()
	 */
	public function readParameters() {
		parent::readParameters();
		
		if (isset($_REQUEST['id'])) $this->conversationID = intval($_REQUEST['id']);
		if (isset($_REQUEST['messageID'])) $this->messageID = intval($_REQUEST['messageID']);
		if ($this->messageID) {
			$this->message = new ConversationMessage($this->messageID);
			if (!$this->message->messageID) {
				throw new IllegalLinkException();
			}
			$this->conversationID = $this->message->messageID;
		}
		
		$this->conversation = Conversation::getUserConversation($this->conversationID, WCF::getUser()->userID);
		if ($this->conversation === null) {
			throw new IllegalLinkException();
		}
		if (!$this->conversation->canRead()) {
			throw new PermissionDeniedException();
		}
		
		// posts per page
		if (WCF::getUser()->conversationMessagesPerPage) $this->itemsPerPage = WCF::getUser()->conversationMessagesPerPage;
	}
	
	/**
	 * @see wcf\page\MultipleLinkPage::initObjectList()
	 */
	protected function initObjectList() {
		parent::initObjectList();
		
		$this->objectList->getConditionBuilder()->add('conversation_message.conversationID = ?', array($this->conversation->conversationID));
		
		// handle jump to
		if ($this->action == 'lastPost') $this->goToLastPost();
		if ($this->action == 'firstNew') $this->goToFirstNewPost();
		if ($this->messageID) $this->goToPost();
	}
	
	/**
	 * @see wcf\page\IPage::readData()
	 */
	public function readData() {
		parent::readData();
		
		// add breadcrumbs
		WCF::getBreadcrumbs()->add(new Breadcrumb(WCF::getLanguage()->get('wcf.conversation.conversations'), LinkHandler::getInstance()->getLink('ConversationList')));
		
		// update last visit time count
		if ($this->conversation->isNew() && $this->objectList->getMaxPostTime() > $this->conversation->lastVisitTime) {
			$conversationAction = new ConversationAction(array($this->conversation), 'markAsRead', array('visitTime' => $this->objectList->getMaxPostTime()));
			$conversationAction->executeAction();
		}
	}
	
	/**
	 * @see wcf\page\IPage::assignVariables()
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		WCF::getTPL()->assign(array(
			'attachmentList' => $this->objectList->getAttachmentList(),
			'sidebarFactory' => $this->sidebarFactory,
			'sortOrder' => $this->sortOrder,
			'conversation' => $this->conversation,
			'conversationID' => $this->conversationID
		));
	}
	
	/**
	 * Calculates the position of a specific post in this conversation.
	 */
	protected function goToPost() {
		$conditionBuilder = clone $this->objectList->getConditionBuilder();
		$conditionBuilder->add('time '.($this->sortOrder == 'ASC' ? '<=' : '>=').' ?', array($this->message->time));
		
		$sql = "SELECT	COUNT(*) AS messages
			FROM 	wcf".WCF_N."_conversation_message conversation_message
			".$conditionBuilder;
		$statement = WCF::getDB()->prepareStatement($sql);	
		$statement->execute($conditionBuilder->getParameters());	
		$row = $statement->fetchArray();
		$this->pageNo = intval(ceil($row['messages'] / $this->itemsPerPage));
	}
	
	/**
	 * Gets the id of the last post in this conversation and forwards the user to this post.
	 */
	protected function goToLastPost() {
		$sql = "SELECT		conversation_message.messageID
			FROM 		wcf".WCF_N."_conversation_message conversation_message
			".$this->objectList->getConditionBuilder()."
			ORDER BY 	time ".($this->sortOrder == 'ASC' ? 'DESC' : 'ASC');
		$statement = WCF::getDB()->prepareStatement($sql, 1);	
		$statement->execute($this->objectList->getConditionBuilder()->getParameters());	
		$row = $statement->fetchArray();
		HeaderUtil::redirect(LinkHandler::getInstance()->getLink('Conversation', array(
			'object' => $this->conversation,
			'messageID' => $row['messageID']
		)).'#message'.$row['messageID']);
		exit;
	}
	
	/**
	 * Forwards the user to the first new message in this conversation.
	 */
	protected function goToFirstNewPost() {
		$conditionBuilder = clone $this->objectList->getConditionBuilder();
		$conditionBuilder->add('time > ?', array($this->conversation->lastVisitTime));
		
		$sql = "SELECT		conversation_message.messageID
			FROM 		wcf".WCF_N."_conversation_message conversation_message
			".$conditionBuilder."
			ORDER BY 	time ASC";
		$statement = WCF::getDB()->prepareStatement($sql, 1);	
		$statement->execute($conditionBuilder->getParameters());	
		$row = $statement->fetchArray();
		if ($row !== false) {
			HeaderUtil::redirect(LinkHandler::getInstance()->getLink('Conversation', array(
				'object' => $this->conversation,
				'messageID' => $row['messageID']
			)).'#message'.$row['messageID']);
			exit;
		}
		else {
			$this->goToLastPost();
		}
	}
	
}