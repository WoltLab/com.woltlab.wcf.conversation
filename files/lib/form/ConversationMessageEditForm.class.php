<?php
namespace wcf\form;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\message\ConversationMessageAction;
use wcf\data\conversation\message\ViewableConversationMessageList;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationAction;
use wcf\data\user\UserProfile;
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
 * @copyright	2009-2014 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	form
 * @category	Community Framework
 */
class ConversationMessageEditForm extends ConversationAddForm {
	/**
	 * @see	\wcf\page\AbstractPage::$templateName
	 */
	public $templateName = 'conversationMessageEdit';
	
	/**
	 * message id
	 * @var	integer
	 */
	public $messageID = 0;
	
	/**
	 * message object
	 * @var	\wcf\data\conversation\message\ConversationMessage
	 */
	public $message = null;
	
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
	 * true if current message is first message
	 * @var	boolean
	 */
	public $isFirstMessage = false;
	
	/**
	 * @see	\wcf\form\IPage::readParameters()
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
		
		if ($this->conversation->firstMessageID == $this->message->messageID) {
			$this->isFirstMessage = true;
		}
		
		// set attachment object id
		$this->attachmentObjectID = $this->message->messageID;
	}
	
	/**
	 * @see	\wcf\form\IForm::readFormParameters()
	 */
	public function readFormParameters() {
		parent::readFormParameters();
		
		if (!$this->conversation->isDraft) $this->draft = 0;
	}
	
	/**
	 * @see	\wcf\form\IForm::validate()
	 */
	public function validate() {
		if ($this->isFirstMessage && $this->conversation->isDraft) parent::validate();
		else MessageForm::validate();
	}
	
	/**
	 * @see	\wcf\form\MessageForm::validateSubject()
	 */
	protected function validateSubject() {
		if ($this->isFirstMessage) parent::validateSubject();
	}
	
	/**
	 * @see	\wcf\form\IForm::save()
	 */
	public function save() {
		MessageForm::save();
		
		// save message
		$data = array_merge($this->additionalFields, array(
			'message' => $this->text,
			'enableBBCodes' => $this->enableBBCodes,
			'enableHtml' => $this->enableHtml,
			'enableSmilies' => $this->enableSmilies,
			'showSignature' => $this->showSignature
		));
		if ($this->conversation->isDraft && !$this->draft) {
			$data['time'] = TIME_NOW;
		}
		$messageData = array(
			'data' => $data,
			'attachmentHandler' => $this->attachmentHandler
		);
		$this->objectAction = new ConversationMessageAction(array($this->message), 'update', $messageData);
		$this->objectAction->executeAction();
		
		// update conversation
		if ($this->isFirstMessage) {
			$data = array(
				'subject' => $this->subject,
				'isDraft' => ($this->draft ? 1 : 0),
				'participantCanInvite' => $this->participantCanInvite
			);
			if ($this->draft) {
				$data['draftData'] = serialize(array(
					'participants' => $this->participantIDs,
					'invisibleParticipants' => $this->invisibleParticipantIDs
				));
			}
			
			$conversationData = array(
				'data' => $data
			);
			if ($this->conversation->isDraft && !$this->draft) {
				$conversationData['participants'] = $this->participantIDs;
				$conversationData['invisibleParticipants'] = $this->invisibleParticipantIDs;
				
				$conversationData['data']['time'] = $conversationData['data']['lastPostTime'] = TIME_NOW;
			}
			
			$conversationAction = new ConversationAction(array($this->conversation), 'update', $conversationData);
			$conversationAction->executeAction();
		}
		$this->saved();
		
		// forward
		HeaderUtil::redirect(LinkHandler::getInstance()->getLink('Conversation', array(
			'object' => $this->conversation,
			'messageID' => $this->messageID
		)).'#message'.$this->messageID);
		exit;
	}
	
	/**
	 * @see	\wcf\page\IPage::readData()
	 */
	public function readData() {
		MessageForm::readData();
		
		if (empty($_POST)) {
			$this->text = $this->message->message;
			
			if ($this->isFirstMessage) {
				$this->participantCanInvite = $this->conversation->participantCanInvite;
				$this->subject = $this->conversation->subject;
				
				if ($this->conversation->isDraft && $this->conversation->draftData) {
					$draftData = @unserialize($this->conversation->draftData);
					if (!empty($draftData['participants'])) {
						foreach (UserProfile::getUserProfiles($draftData['participants']) as $user) {
							if (!empty($this->participants)) $this->participants .= ', ';
							$this->participants .= $user->username;
						}
					}
					if (!empty($draftData['invisibleParticipants'])) {
						foreach (UserProfile::getUserProfiles($draftData['invisibleParticipants']) as $user) {
							if (!empty($this->invisibleParticipants)) $this->invisibleParticipants .= ', ';
							$this->invisibleParticipants .= $user->username;
						}
					}
				}
			}
		}
		
		// add breadcrumbs
		WCF::getBreadcrumbs()->add(new Breadcrumb(WCF::getLanguage()->get('wcf.conversation.conversations'), LinkHandler::getInstance()->getLink('ConversationList')));
		WCF::getBreadcrumbs()->add($this->conversation->getBreadcrumb());
		
		// get message list
		$this->messageList = new ViewableConversationMessageList();
		$this->messageList->sqlLimit = CONVERSATION_REPLY_SHOW_MESSAGES_MAX;
		$this->messageList->sqlOrderBy = 'conversation_message.time DESC';
		$this->messageList->getConditionBuilder()->add('conversation_message.conversationID = ?', array($this->message->conversationID));
		$this->messageList->getConditionBuilder()->add("conversation_message.messageID <> ?", array($this->message->messageID));
		$this->messageList->readObjects();
	}
	
	/**
	 * @see	\wcf\page\IPage::assignVariables()
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		WCF::getTPL()->assign(array(
			'messageID' => $this->messageID,
			'message' => $this->message,
			'conversationID' => $this->conversationID,
			'conversation' => $this->conversation,
			'isFirstMessage' => $this->isFirstMessage,
			'items' => $this->messageList->countObjects(),
			'messages' => $this->messageList->getObjects(),
			'attachmentList' => $this->messageList->getAttachmentList()
		));
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
