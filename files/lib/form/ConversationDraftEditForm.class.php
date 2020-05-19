<?php
namespace wcf\form;
use wcf\data\conversation\message\ConversationMessageAction;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationAction;
use wcf\data\user\UserProfile;
use wcf\system\exception\IllegalLinkException;
use wcf\system\message\quote\MessageQuoteManager;
use wcf\system\WCF;
use wcf\util\HeaderUtil;

/**
 * Allows the editing of conversation drafts.
 *
 * @author	Marcel Werk
 * @copyright	2001-2019 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\Form
 */
class ConversationDraftEditForm extends ConversationAddForm {
	/**
	 * @inheritDoc
	 */
	public $templateName = 'conversationAdd';
		
	/**
	 * conversation id
	 * @var	integer
	 */
	public $conversationID = 0;
	
	/**
	 * conversation
	 * @var	Conversation
	 */
	public $conversation;
	
	/**
	 * @inheritDoc
	 */
	public function readParameters() {
		parent::readParameters();
		
		if (isset($_REQUEST['id'])) $this->conversationID = intval($_REQUEST['id']);
		$this->conversation = new Conversation($this->conversationID);
		if ($this->conversation->userID != WCF::getUser()->userID || !$this->conversation->isDraft) {
			throw new IllegalLinkException();
		}
		
		$this->attachmentObjectID = $this->conversation->getFirstMessage()->messageID;
	}
	
	/**
	 * @inheritDoc
	 */
	public function save() {
		MessageForm::save();
		
		// save message
		$messageData = [
			'data' => [],
			'attachmentHandler' => $this->attachmentHandler,
			'htmlInputProcessor' => $this->htmlInputProcessor,
		];
		if (!$this->draft) {
			// update timestamp
			$messageData['data']['time'] = TIME_NOW;
		}
		$messageAction = new ConversationMessageAction([$this->conversation->getFirstMessage()], 'update', $messageData);
		$messageAction->executeAction();
		
		// save conversation
		$data = array_merge($this->additionalFields, [
			'subject' => $this->subject,
			'isDraft' => $this->draft ? 1 : 0,
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
		];
		if (!$this->draft) {
			$conversationData['participants'] = $this->participantIDs;
			$conversationData['invisibleParticipants'] = $this->invisibleParticipantIDs;
			// update timestamp
			$conversationData['data']['time'] = $conversationData['data']['lastPostTime'] = TIME_NOW;
		}
		$this->objectAction = new ConversationAction([$this->conversation], 'update', $conversationData);
		$this->objectAction->executeAction();
		
		MessageQuoteManager::getInstance()->saved();
		$this->saved();
		
		// forward
		HeaderUtil::redirect($this->conversation->getLink());
		exit;
	}
	
	/**
	 * @inheritDoc
	 */
	public function readData() {
		parent::readData();
		
		if (empty($_POST)) {
			$this->text = $this->conversation->getFirstMessage()->message;
			$this->participantCanInvite = $this->conversation->participantCanInvite;
			$this->subject = $this->conversation->subject;
			
			if ($this->conversation->draftData) {
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
	
	/**
	 * @inheritDoc
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		WCF::getTPL()->assign([
			'conversationID' => $this->conversationID,
			'conversation' => $this->conversation,
			'action' => 'edit'
		]);
	}
}
