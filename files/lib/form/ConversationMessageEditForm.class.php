<?php
namespace wcf\form;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\message\ConversationMessageAction;
use wcf\data\conversation\message\ViewableConversationMessageList;
use wcf\data\conversation\message\ConversationMessage;
use wcf\system\breadcrumb\Breadcrumb;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\HeaderUtil;

/**
 * Shows the conversation message edit form.
 *
 * @author	Marcel Werk
 * @copyright	2009-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	form
 * @category 	Community Framework
 */
class ConversationMessageEditForm extends ConversationMessageAddForm {
	/**
	 * @see wcf\page\AbstractPage::$templateName
	 */
	public $templateName = 'conversationMessageEdit';
	
	/**
	 * message id
	 * @var integer
	 */
	public $messageID = 0;
	
	/**
	 * message object
	 * @var wcf\data\conversation\message\ConversationMessage
	 */
	public $message = null;
	
	/**
	 * @see wcf\form\IPage::readParameters()
	 */
	public function readParameters() {
		MessageForm::readParameters();
		
		if (isset($_REQUEST['id'])) $this->messageID = intval($_REQUEST['id']);
		$this->message = new ConversationMessage($this->messageID);
		if (!$this->message->messageID) {
			throw new IllegalLinkException();
		}
		if ($this->message->userID != WCF::getUser()->userID) {
			throw new PermissionDeniedException();
		}
		// get conversation
		$this->conversationID = $this->message->conversationID;
		$this->conversation = new Conversation($this->conversationID);
	}
	
	/**
	 * @see wcf\form\IForm::save()
	 */
	public function save() {
		MessageForm::save();
		
		// save message
		$data = array(
			'message' => $this->text
		);
		
		$messageData = array(
			'data' => $data,
			'attachmentHandler' => $this->attachmentHandler
		);
		
		$this->objectAction = new ConversationMessageAction(array($this->message), 'update', $messageData);
		$this->objectAction->executeAction();
		$this->saved();
		
		// forward
		HeaderUtil::redirect(LinkHandler::getInstance()->getLink('Conversation', array(
			'object' => $this->conversation,
			'messageID' => $this->messageID
		)).'#message'.$this->messageID);
		exit;
	}
	
	/**
	 * @see wcf\page\IPage::readData()
	 */
	public function readData() {
		MessageForm::readData();
		
		if (!count($_POST)) {
			$this->text = $this->message->message;
		}
		
		// add breadcrumbs
		WCF::getBreadcrumbs()->add(new Breadcrumb(WCF::getLanguage()->get('wcf.conversation.conversations'), LinkHandler::getInstance()->getLink('ConversationList')));
		WCF::getBreadcrumbs()->add($this->conversation->getBreadcrumb());
		
		// get message list
		$this->messageList = new ViewableConversationMessageList();
		$this->messageList->sqlLimit = 10; //todo add setting? REPLY_SHOW_POSTS_MAX;
		$this->messageList->sqlOrderBy = 'conversation_message.time DESC';
		$this->messageList->getConditionBuilder()->add('conversation_message.conversationID = ?', array($this->message->conversationID));
		$this->messageList->getConditionBuilder()->add("conversation_message.messageID <> ?", array($this->message->messageID));
		$this->messageList->readObjects();
	}
	
	/**
	 * @see wcf\page\IPage::assignVariables()
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		WCF::getTPL()->assign(array(
			'messageID' => $this->messageID,
			'message' => $this->message
		));
	}
}