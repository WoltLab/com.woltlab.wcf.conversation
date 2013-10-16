<?php
namespace wcf\page;
use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationAction;
use wcf\data\conversation\ConversationParticipantList;
use wcf\data\conversation\ViewableConversation;
use wcf\data\modification\log\ConversationLogModificationLogList;
use wcf\data\smiley\SmileyCache;
use wcf\system\bbcode\BBCodeHandler;
use wcf\system\breadcrumb\Breadcrumb;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\message\quote\MessageQuoteManager;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\HeaderUtil;

/**
 * Shows a conversation.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2013 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	page
 * @category	Community Framework
 */
class ConversationPage extends MultipleLinkPage {
	/**
	 * @see	\wcf\page\AbstractPage::$enableTracking
	 */
	public $enableTracking = true;
	
	/**
	 * @see	\wcf\page\MultipleLinkPage::$itemsPerPage
	 */
	public $itemsPerPage = CONVERSATION_MESSAGES_PER_PAGE;
	
	/**
	 * @see	\wcf\page\MultipleLinkPage::$sortOrder
	 */
	public $sortOrder = 'ASC';
	
	/**
	 * @see	\wcf\page\MultipleLinkPage::$objectListClassName
	 */
	public $objectListClassName = 'wcf\data\conversation\message\ViewableConversationMessageList';
	
	/**
	 * @see	\wcf\page\AbstractPage::$loginRequired
	 */
	public $loginRequired = true;
	
	/**
	 * @see	\wcf\page\AbstractPage::$neededModules
	 */
	public $neededModules = array('MODULE_CONVERSATION');
	
	/**
	 * @see	\wcf\page\AbstractPage::$neededPermissions
	 */
	public $neededPermissions = array('user.conversation.canUseConversation');
	
	/**
	 * conversation id
	 * @var	integer
	 */
	public $conversationID = 0;
	
	/**
	 * viewable conversation object
	 * @var	\wcf\data\conversation\ViewableConversation
	 */
	public $conversation = null;
	
	/**
	 * conversation label list
	 * @var	\wcf\data\conversation\label\ConversationLabelList
	 */
	public $labelList = null;
	
	/**
	 * message id
	 * @var	integer
	 */
	public $messageID = 0;
	
	/**
	 * conversation message object
	 * @var	\wcf\data\conversation\message\ConversationMessage
	 */
	public $message = null;
	
	/**
	 * modification log list object
	 * @var	\wcf\data\wcf\data\modification\log\ConversationLogModificationLogList
	 */
	public $modificationLogList = null;
	
	/**
	 * list of participants
	 * @var	\wcf\data\conversation\ConversationParticipantList
	 */
	public $participantList = null;
	
	/**
	 * @see	\wcf\page\IPage::readParameters()
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
			$this->conversationID = $this->message->conversationID;
		}
		
		$this->conversation = Conversation::getUserConversation($this->conversationID, WCF::getUser()->userID);
		if ($this->conversation === null) {
			throw new IllegalLinkException();
		}
		if (!$this->conversation->canRead()) {
			throw new PermissionDeniedException();
		}
		
		// load labels
		$this->labelList = ConversationLabel::getLabelsByUser();
		$this->conversation = ViewableConversation::getViewableConversation($this->conversation, $this->labelList);
		
		// posts per page
		if (WCF::getUser()->conversationMessagesPerPage) $this->itemsPerPage = WCF::getUser()->conversationMessagesPerPage;
	}
	
	/**
	 * @see	\wcf\page\MultipleLinkPage::initObjectList()
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
	 * @see	\wcf\page\IPage::readData()
	 */
	public function readData() {
		parent::readData();
		
		// add breadcrumbs
		WCF::getBreadcrumbs()->add(new Breadcrumb(WCF::getLanguage()->get('wcf.conversation.conversations'), LinkHandler::getInstance()->getLink('ConversationList')));
		if ($this->conversation->isDraft) {
			WCF::getBreadcrumbs()->add(new Breadcrumb(WCF::getLanguage()->get('wcf.conversation.folder.draft'), LinkHandler::getInstance()->getLink('ConversationList', array(
				'filter' => 'draft'
			))));
		}
		
		// update last visit time count
		if ($this->conversation->isNew() && $this->objectList->getMaxPostTime() > $this->conversation->lastVisitTime) {
			$visitTime = $this->objectList->getMaxPostTime();
			if ($visitTime == $this->conversation->lastPostTime) $visitTime = TIME_NOW;
			$conversationAction = new ConversationAction(array($this->conversation->getDecoratedObject()), 'markAsRead', array('visitTime' => $visitTime));
			$conversationAction->executeAction();
		}
		
		// get participants
		$this->participantList = new ConversationParticipantList($this->conversationID, WCF::getUser()->userID, ($this->conversation->userID == WCF::getUser()->userID));
		$this->participantList->readObjects();
		
		// init quote objects
		$messageIDs = array();
		foreach ($this->objectList as $message) {
			$messageIDs[] = $message->messageID;
		}
		MessageQuoteManager::getInstance()->initObjects('com.woltlab.wcf.conversation.message', $messageIDs);
		
		// set attachment permissions
		if ($this->objectList->getAttachmentList() !== null) {
			$this->objectList->getAttachmentList()->setPermissions(array(
				'canDownload' => true,
				'canViewPreview' => true
			));
		}
		
		// get timeframe for modifications
		$this->objectList->rewind();
		$startTime = $this->objectList->current()->time;
		
		$count = count($this->objectList);
		if ($count == 1) {
			$endTime = $startTime;
		}
		else {
			$this->objectList->seek($count - 1);
			$endTime = $this->objectList->current()->time;
		}
		$this->objectList->rewind();
		
		// load modification log entries
		$this->modificationLogList = new ConversationLogModificationLogList();
		$this->modificationLogList->setConversation($this->conversation->getDecoratedObject());
		$this->modificationLogList->getConditionBuilder()->add("modification_log.time BETWEEN ? AND ?", array($startTime, $endTime));
		$this->modificationLogList->readObjects();
	}
	
	/**
	 * @see	\wcf\page\IPage::assignVariables()
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		MessageQuoteManager::getInstance()->assignVariables();
		
		WCF::getTPL()->assign(array(
			'attachmentList' => $this->objectList->getAttachmentList(),
			'labelList' => $this->labelList,
			'modificationLogList' => $this->modificationLogList,
			'sortOrder' => $this->sortOrder,
			'conversation' => $this->conversation,
			'conversationID' => $this->conversationID,
			'participants' => $this->participantList->getObjects(),
			'defaultSmilies' => SmileyCache::getInstance()->getCategorySmilies(),
			'permissionCanUseSmilies' => 'user.message.canUseSmilies'
		));
		
		BBCodeHandler::getInstance()->setAllowedBBCodes(explode(',', WCF::getSession()->getPermission('user.message.allowedBBCodes')));
	}
	
	/**
	 * Calculates the position of a specific post in this conversation.
	 */
	protected function goToPost() {
		$conditionBuilder = clone $this->objectList->getConditionBuilder();
		$conditionBuilder->add('time '.($this->sortOrder == 'ASC' ? '<=' : '>=').' ?', array($this->message->time));
		
		$sql = "SELECT	COUNT(*) AS messages
			FROM	wcf".WCF_N."_conversation_message conversation_message
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
			FROM		wcf".WCF_N."_conversation_message conversation_message
			".$this->objectList->getConditionBuilder()."
			ORDER BY	time ".($this->sortOrder == 'ASC' ? 'DESC' : 'ASC');
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
			FROM		wcf".WCF_N."_conversation_message conversation_message
			".$conditionBuilder."
			ORDER BY	time ASC";
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
	
	/**
	 * @see	\wcf\page\ITrackablePage::getObjectType()
	 */
	public function getObjectType() {
		return 'com.woltlab.wcf.conversation';
	}
	
	/**
	 * @see	\wcf\page\ITrackablePage::getObjectID()
	 */
	public function getObjectID() {
		return $this->conversationID;
	}
}
