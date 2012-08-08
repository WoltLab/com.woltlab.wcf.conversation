<?php
namespace wcf\form;
use wcf\data\conversation\message\ConversationMessageAction;
use wcf\data\conversation\message\ViewableConversationMessageList;
use wcf\data\conversation\Conversation;
use wcf\system\breadcrumb\Breadcrumb;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
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
 * @category 	Community Framework
 */
class ConversationMessageAddForm extends MessageForm {
	/**
	 * @see wcf\form\MessageForm::$attachmentObjectType
	 */
	public $attachmentObjectType = 'com.woltlab.wcf.conversation.message';
	
	/**
	 * @see wcf\page\AbstractPage::$loginRequired
	 */
	public $loginRequired = true;
	
	/**
	 * @see wcf\page\AbstractPage::$neededModules
	 */
	public $neededModules = array('MODULE_CONVERSATION');
	
	/**
	 * @see wcf\page\AbstractPage::$neededPermissions
	 */
	public $neededPermissions = array('user.conversation.canUseConversation');
	
	/**
	 * conversation id
	 * @var integer
	 */
	public $conversationID = 0;
	
	/**
	 * conversation
	 * @var wcf\data\conversation\Conversation
	 */
	public $conversation = null;
	
	/**
	 * message list
	 * @var wcf\data\conversation\message\ConversationMessageList
	 */
	public $messageList = null;
	
	/**
	 * @see wcf\form\IPage::readParameters()
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
	}
	
	/**
	 * @see wcf\form\MessageForm::validateSubject()
	 */
	protected function validateSubject() {}
	
	/**
	 * @see wcf\page\IPage::readData()
	 */
	public function readData() {
		parent::readData();
		
		// add breadcrumbs
		WCF::getBreadcrumbs()->add(new Breadcrumb(WCF::getLanguage()->get('wcf.conversation.conversations'), LinkHandler::getInstance()->getLink('ConversationList')));
		WCF::getBreadcrumbs()->add($this->conversation->getBreadcrumb());
		
		// get message list
		$this->messageList = new ViewableConversationMessageList();
		$this->messageList->sqlLimit = 10; // @todo add setting? REPLY_SHOW_POSTS_MAX;
		$this->messageList->sqlOrderBy = 'conversation_message.time DESC';
		$this->messageList->getConditionBuilder()->add('conversation_message.conversationID = ?', array($this->conversation->conversationID));
		$this->messageList->readObjects();
	}
	
	/**
	 * @see wcf\form\IForm::save()
	 */
	public function save() {
		parent::save();
		
		// save message
		$data = array(
			'conversationID' => $this->conversationID,	
			'message' => $this->text,
			'time' => TIME_NOW,
			'userID' => WCF::getUser()->userID,
			'username' => WCF::getUser()->username
		);
		
		$messageData = array(
			'data' => $data,
			'attachmentHandler' => $this->attachmentHandler
		);
		
		$this->objectAction = new ConversationMessageAction(array(), 'create', $messageData);
		$resultValues = $this->objectAction->executeAction();
		$this->saved();
		
		// forward
		HeaderUtil::redirect(LinkHandler::getInstance()->getLink('Conversation', array(
			'object' => $this->conversation,
			'messageID' => $resultValues['returnValues']->messageID
		)).'#message'.$resultValues['returnValues']->messageID);
		exit;
	}
	
	/**
	 * @see wcf\page\IPage::assignVariables()
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		WCF::getTPL()->assign(array(
			'conversation' => $this->conversation,
			'conversationID' => $this->conversationID,
			'items' => $this->messageList->countObjects(),
			'messages' => $this->messageList->getObjects(),
			'attachmentList' => $this->messageList->getAttachmentList()
		));
	}
} 