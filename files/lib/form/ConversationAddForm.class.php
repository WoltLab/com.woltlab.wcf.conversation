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
 * @copyright	2001-2015 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	form
 * @category	Community Framework
 */
class ConversationAddForm extends MessageForm {
	/**
	 * @see	\wcf\page\AbstractPage::$enableTracking
	 */
	public $enableTracking = true;
	
	/**
	 * @see	\wcf\form\MessageForm::$attachmentObjectType
	 */
	public $attachmentObjectType = 'com.woltlab.wcf.conversation.message';
	
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
	public $participantIDs = array();
	
	/**
	 * invisible participants (user ids)
	 * @var	integer[]
	 */
	public $invisibleParticipantIDs = array();
	
	/**
	 * @see	\wcf\page\IPage::readParameters()
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
				throw new NamedUserException(WCF::getLanguage()->getDynamicVariable('wcf.conversation.participants.error.'.$e->getType(), array('errorData' => array('username' => $user->username))));
			}
			
			$this->participants = $user->username;
		}
		
		// get max text length
		$this->maxTextLength = WCF::getSession()->getPermission('user.conversation.maxLength');
		
		// quotes
		MessageQuoteManager::getInstance()->readParameters();
	}
	
	/**
	 * @see	\wcf\form\IForm::readFormParameters()
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
	 * @see	\wcf\form\IForm::validate()
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
	 * @see	\wcf\form\IForm::save()
	 */
	public function save() {
		parent::save();
		
		// save conversation
		$data = array_merge($this->additionalFields, array(
			'subject' => $this->subject,
			'time' => TIME_NOW,
			'userID' => WCF::getUser()->userID,
			'username' => WCF::getUser()->username,
			'isDraft' => ($this->draft ? 1 : 0),
			'participantCanInvite' => $this->participantCanInvite
		));
		if ($this->draft) {
			$data['draftData'] = serialize(array(
				'participants' => $this->participantIDs,
				'invisibleParticipants' => $this->invisibleParticipantIDs
			));
		}
		
		$conversationData = array(
			'data' => $data,
			'attachmentHandler' => $this->attachmentHandler,
			'messageData' => array(
				'message' => $this->text,
				'enableBBCodes' => $this->enableBBCodes,
				'enableHtml' => $this->enableHtml,
				'enableSmilies' => $this->enableSmilies,
				'showSignature' => $this->showSignature
			)
		);
		if (!$this->draft) {
			$conversationData['participants'] = $this->participantIDs;
			$conversationData['invisibleParticipants'] = $this->invisibleParticipantIDs;
		}
		
		$this->objectAction = new ConversationAction(array(), 'create', $conversationData);
		$resultValues = $this->objectAction->executeAction();
		
		MessageQuoteManager::getInstance()->saved();
		
		$this->saved();
		
		// forward
		HeaderUtil::redirect(LinkHandler::getInstance()->getLink('Conversation', array(
			'object' => $resultValues['returnValues']
		)));
		exit;
	}
	
	/**
	 * @see	\wcf\page\IPage::readData()
	 */
	public function readData() {
		parent::readData();
		
		// add breadcrumbs
		PageLocationManager::getInstance()->addParentLocation('com.woltlab.wcf.conversation.ConversationList');
	}
	
	/**
	 * @see	\wcf\page\IPage::assignVariables()
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		MessageQuoteManager::getInstance()->assignVariables();
		
		WCF::getTPL()->assign(array(
			'participantCanInvite' => $this->participantCanInvite,
			'participants' => $this->participants,
			'invisibleParticipants' => $this->invisibleParticipants
		));
	}
}
