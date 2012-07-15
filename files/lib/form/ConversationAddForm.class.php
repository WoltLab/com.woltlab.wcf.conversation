<?php
namespace wcf\form;
use wcf\data\conversation\ConversationAction;
use wcf\data\user\UserProfile;
use wcf\system\breadcrumb\Breadcrumb;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\HeaderUtil;
use wcf\util\StringUtil;

/**
 * Shows the conversation form.
 *
 * @author	Marcel Werk
 * @copyright	2009-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	form
 * @category 	Community Framework
 */
class ConversationAddForm extends MessageForm {
	/**
	 * @see wcf\form\MessageForm::$attachmentObjectType
	 */
	public $attachmentObjectType = 'com.woltlab.wcf.conversation.message';
	
	/**
	 * @see wcf\page\AbstractPage::$neededModules
	 */
	public $neededModules = array('MODULE_CONVERSATION');
	
	/**
	 * @see wcf\page\AbstractPage::$neededPermissions
	 */
	public $neededPermissions = array('user.conversation.canUseConversation');
	
	/**
	 * participants (comma separated user names)
	 * @var string
	 */
	public $participants = '';
	
	/**
	 * invisible participants (comma separated user names)
	 * @var string
	 */
	public $invisibleParticipants = '';
	
	/**
	 * draft status
	 * @var integer
	 */
	public $draft = 0;
	
	/**
	 * participants (user ids)
	 * @var array<integer>
	 */
	public $participantIDs = array();
	
	/**
	 * invisible participants (user ids)
	 * @var array<integer>
	 */
	public $invisibleParticipantIDs = array();
	
	/**
	 * @see wcf\form\IForm::readFormParameters()
	 */
	public function readFormParameters() {
		parent::readFormParameters();
		
		if (isset($_POST['draft'])) $this->draft = intval($_POST['draft']);
		if (isset($_POST['participants'])) $this->participants = StringUtil::trim($_POST['participants']);
		if (isset($_POST['invisibleParticipants'])) $this->invisibleParticipants = StringUtil::trim($_POST['invisibleParticipants']);
	}
	
	/**
	 * @see wcf\form\IForm::validate()
	 */
	public function validate() {
		if (empty($this->participants) && empty($this->invisibleParticipants) && !$this->draft) {
			throw new UserInputException('participants');
		}
		
		$this->participantIDs = $this->validateParticipants($this->participants);
		$this->invisibleParticipantIDs = $this->validateParticipants($this->invisibleParticipants, 'invisibleParticipants');
		
		// remove duplicates
		$intersection = array_intersect($this->participantIDs, $this->invisibleParticipantIDs);
		if (!empty($intersection)) $this->invisibleParticipantIDs = array_diff($this->invisibleParticipantIDs, $intersection);
		
		if (!count($this->participantIDs) && !count($this->invisibleParticipantIDs) && !$this->draft) {
			throw new UserInputException('participants');
		}
		
		// check number of participants
		if (count($this->participantIDs) + count($this->invisibleParticipantIDs) > WCF::getSession()->getPermission('user.conversation.maxParticipants')) {
			throw new UserInputException('participants', 'tooManyParticipants');
		}
		
		parent::validate();
	}
	
	/**
	 * Validates the participants.
	 */
	protected function validateParticipants($participants, $field = 'participants') {
		// explode multiple participants to an array
		$participantList = explode(',', $participants);
		$result = array();
		$error = array();
		
		// loop through participants and check their settings
		foreach ($participantList as $participant) {
			$participant = StringUtil::trim($participant);
			if (empty($participant)) continue;
			
			try {
				// get participant's profile
				$user = UserProfile::getUserProfileByUsername($participant);
				if ($user === null) {
					throw new UserInputException('participant', 'notFound');
				}
				if (in_array($user->userID, $result)) continue; // ignore duplicates
				if ($user->userID == WCF::getUser()->userID) continue; // ignore author
				
				// todo: check participant's settings and permissions
				/*if (!$user->getPermission('user.conversation.canUseConversation')) {
					throw new UserInputException('participant', 'canNotUseConversation');
				}*/
				
				// check privacy setting
				if ($user->canSendConversation == 2 || ($user->canSendConversation == 1 && WCF::getProfileHandler()->isFollowing($user->userID))) {
					throw new UserInputException('participant', 'doesNotAcceptConversation');
				}
				
				// active user is ignored by participant
				if ($user->isIgnoredUser(WCF::getUser()->userID)) {
					throw new UserInputException('participant', 'ignoresYou');
				}
				
				// todo: check participant's mailbox quota
				if (false) {
					throw new UserInputException('participant', 'mailboxIsFull');
				}
				
				// no error
				$result[] = $user->userID;
			}
			catch (UserInputException $e) {
				$error[] = array('type' => $e->getType(), 'username' => $participant);
			}
		}
		
		if (count($error)) {
			throw new UserInputException($field, $error);
		}
		
		return $result;
	}
	
	/**
	 * @see wcf\form\IForm::save()
	 */
	public function save() {
		parent::save();
		
		// save conversation
		$data = array(
			'subject' => $this->subject,
			'time' => TIME_NOW,
			'userID' => WCF::getUser()->userID,
			'username' => WCF::getUser()->username
		);
		
		$conversationData = array(
			'data' => $data,
			'participants' => $this->participantIDs,
			'invisibleParticipants' => $this->invisibleParticipantIDs,
			'attachmentHandler' => $this->attachmentHandler,
			'messageData' => array(
				'message' => $this->text
			)
		);
		
		$this->objectAction = new ConversationAction(array(), 'create', $conversationData);
		$resultValues = $this->objectAction->executeAction();
		$this->saved();
		
		// forward
		HeaderUtil::redirect(LinkHandler::getInstance()->getLink('Conversation', array(
			'object' => $resultValues['returnValues']
		)));
		exit;
	}
	
	/**
	 * @see wcf\page\IPage::readData()
	 */
	public function readData() {
		parent::readData();
		
		// add breadcrumbs
		WCF::getBreadcrumbs()->add(new Breadcrumb(WCF::getLanguage()->get('wcf.conversation.conversations'), LinkHandler::getInstance()->getLink('ConversationList')));
	}
	
	/**
	 * @see wcf\page\IPage::assignVariables()
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		WCF::getTPL()->assign(array(
			'participants' => $this->participants,
			'invisibleParticipants' => $this->invisibleParticipants
		));
	}
	
	/**
	 * @see wcf\page\IPage::show()
	 */
	public function show() {
		if (!WCF::getUser()->userID) {
			throw new PermissionDeniedException();
		}
		
		parent::show();
	}
}