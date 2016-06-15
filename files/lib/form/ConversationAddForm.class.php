<?php
namespace wcf\form;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationAction;
use wcf\data\user\UserProfile;
use wcf\system\conversation\ConversationHandler;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\NamedUserException;
use wcf\system\exception\UserInputException;
use wcf\system\message\quote\MessageQuoteManager;
use wcf\system\page\PageLocationManager;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\HeaderUtil;
use wcf\util\StringUtil;

/**
 * Shows the conversation form.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\Form
 */
class ConversationAddForm extends MessageForm {
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
	 * participants (comma separated user names)
	 * @var	string
	 */
	public $participants = '';
	
	/**
	 * invisible participants (comma separated user names)
	 * @var	string
	 */
	public $invisibleParticipants = '';
	
	/**
	 * draft status
	 * @var	integer
	 */
	public $draft = 0;
	
	/**
	 * true, if participants can add new participants
	 * @var	integer
	 */
	public $participantCanInvite = 0;
	
	/**
	 * participants (user ids)
	 * @var	integer[]
	 */
	public $participantIDs = [];
	
	/**
	 * invisible participants (user ids)
	 * @var	integer[]
	 */
	public $invisibleParticipantIDs = [];
	
	/**
	 * @inheritDoc
	 */
	public function readParameters() {
		parent::readParameters();
		
		if (!WCF::getUser()->userID) return;
		
		// check max pc permission
		if (ConversationHandler::getInstance()->getConversationCount() >= WCF::getSession()->getPermission('user.conversation.maxConversations')) {
			throw new NamedUserException(WCF::getLanguage()->get('wcf.conversation.error.mailboxIsFull'));
		}
		
		if (isset($_REQUEST['userID'])) {
			$userID = intval($_REQUEST['userID']);
			$user = UserProfile::getUserProfile($userID);
			if ($user === null || $user->userID == WCF::getUser()->userID) {
				throw new IllegalLinkException();
			}
			
			// validate user
			try {
				Conversation::validateParticipant($user);
			}
			catch (UserInputException $e) {
				throw new NamedUserException(WCF::getLanguage()->getDynamicVariable('wcf.conversation.participants.error.'.$e->getType(), ['errorData' => ['username' => $user->username]]));
			}
			
			$this->participants = $user->username;
		}
		
		// get max text length
		$this->maxTextLength = WCF::getSession()->getPermission('user.conversation.maxLength');
		
		// quotes
		MessageQuoteManager::getInstance()->readParameters();
	}
	
	/**
	 * @inheritDoc
	 */
	public function readFormParameters() {
		parent::readFormParameters();
		
		if (isset($_POST['draft'])) $this->draft = (bool) $_POST['draft'];
		if (isset($_POST['participantCanInvite'])) $this->participantCanInvite = (bool) $_POST['participantCanInvite'];
		if (isset($_POST['participants'])) $this->participants = StringUtil::trim($_POST['participants']);
		if (isset($_POST['invisibleParticipants'])) $this->invisibleParticipants = StringUtil::trim($_POST['invisibleParticipants']);
		
		// quotes
		MessageQuoteManager::getInstance()->readFormParameters();
	}
	
	/**
	 * @inheritDoc
	 */
	public function validate() {
		if (empty($this->participants) && empty($this->invisibleParticipants) && !$this->draft) {
			throw new UserInputException('participants');
		}
		
		// check, if user is allowed to set invisible participants
		if (!WCF::getSession()->getPermission('user.conversation.canAddInvisibleParticipants') && !empty($this->invisibleParticipants)) {
			throw new UserInputException('participants', 'invisibleParticipantsNoPermission');
		}
		
		// check, if user is allowed to set participantCanInvite
		if (!WCF::getSession()->getPermission('user.conversation.canSetCanInvite') && $this->participantCanInvite) {
			throw new UserInputException('participantCanInvite', 'participantCanInviteNoPermission');
		}
		
		$this->participantIDs = Conversation::validateParticipants($this->participants);
		$this->invisibleParticipantIDs = Conversation::validateParticipants($this->invisibleParticipants, 'invisibleParticipants');
		
		// remove duplicates
		$intersection = array_intersect($this->participantIDs, $this->invisibleParticipantIDs);
		if (!empty($intersection)) $this->invisibleParticipantIDs = array_diff($this->invisibleParticipantIDs, $intersection);
		
		if (empty($this->participantIDs) && empty($this->invisibleParticipantIDs) && !$this->draft) {
			throw new UserInputException('participants');
		}
		
		// check number of participants
		if (count($this->participantIDs) + count($this->invisibleParticipantIDs) > WCF::getSession()->getPermission('user.conversation.maxParticipants')) {
			throw new UserInputException('participants', 'tooManyParticipants');
		}
		
		parent::validate();
	}
	
	/**
	 * @inheritDoc
	 */
	public function save() {
		parent::save();
		
		// save conversation
		$data = array_merge($this->additionalFields, [
			'subject' => $this->subject,
			'time' => TIME_NOW,
			'userID' => WCF::getUser()->userID,
			'username' => WCF::getUser()->username,
			'isDraft' => ($this->draft ? 1 : 0),
			'participantCanInvite' => $this->participantCanInvite
		]);
		if ($this->draft) {
			$data['draftData'] = serialize([
				'participants' => $this->participantIDs,
				'invisibleParticipants' => $this->invisibleParticipantIDs
			]);
		}
		
		$conversationData = [
			'data' => $data,
			'attachmentHandler' => $this->attachmentHandler,
			'htmlInputProcessor' => $this->htmlInputProcessor,
			'messageData' => []
		];
		if (!$this->draft) {
			$conversationData['participants'] = $this->participantIDs;
			$conversationData['invisibleParticipants'] = $this->invisibleParticipantIDs;
		}
		
		$this->objectAction = new ConversationAction([], 'create', $conversationData);
		$resultValues = $this->objectAction->executeAction();
		
		MessageQuoteManager::getInstance()->saved();
		
		$this->saved();
		
		// forward
		HeaderUtil::redirect(LinkHandler::getInstance()->getLink('Conversation', [
			'object' => $resultValues['returnValues']
		]));
		exit;
	}
	
	/**
	 * @inheritDoc
	 */
	public function readData() {
		parent::readData();
		
		// add breadcrumbs
		PageLocationManager::getInstance()->addParentLocation('com.woltlab.wcf.conversation.ConversationList');
	}
	
	/**
	 * @inheritDoc
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		MessageQuoteManager::getInstance()->assignVariables();
		
		WCF::getTPL()->assign([
			'participantCanInvite' => $this->participantCanInvite,
			'participants' => $this->participants,
			'invisibleParticipants' => $this->invisibleParticipants
		]);
	}
}
