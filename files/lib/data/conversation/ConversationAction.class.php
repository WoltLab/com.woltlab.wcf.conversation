<?php
namespace wcf\data\conversation;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\IClipboardAction;
use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\message\ConversationMessageAction;
use wcf\data\conversation\message\ViewableConversationMessageList;
use wcf\data\user\UserProfile;
use wcf\system\clipboard\ClipboardHandler;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\exception\ValidateActionException;
use wcf\system\package\PackageDependencyHandler;
use wcf\system\user\notification\object\ConversationUserNotificationObject;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;

/**
 * Executes conversation-related actions.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation
 * @category	Community Framework
 */
class ConversationAction extends AbstractDatabaseObjectAction implements IClipboardAction {
	/**
	 * @see	wcf\data\AbstractDatabaseObjectAction::$className
	 */
	protected $className = 'wcf\data\conversation\ConversationEditor';
	
	/**
	 * conversation object
	 * @var	wcf\data\conversation\Conversation
	 */
	protected $conversation = null;
	
	/**
	 * list of conversation data modifications
	 * @var	array<array>
	 */
	protected $conversationData = array();
	
	/**
	 * @see	wcf\data\AbstractDatabaseObjectAction::create()
	 */
	public function create() {
		// create conversation
		$data = $this->parameters['data'];
		$data['lastPosterID'] = $data['userID'];
		$data['lastPoster'] = $data['username'];
		$data['lastPostTime'] = $data['time'];
		// count participants
		if (!empty($this->parameters['participants'])) {
			$data['participants'] = count($this->parameters['participants']);
		}
		// count attachments
		if (isset($this->parameters['attachmentHandler']) && $this->parameters['attachmentHandler'] !== null) {
			$data['attachments'] = count($this->parameters['attachmentHandler']);
		}
		$conversation = call_user_func(array($this->className, 'create'), $data);
		$conversationEditor = new ConversationEditor($conversation);
		
		// save participants
		if (!$conversation->isDraft) {
			$conversationEditor->updateParticipants((!empty($this->parameters['participants']) ? $this->parameters['participants'] : array()), (!empty($this->parameters['invisibleParticipants']) ? $this->parameters['invisibleParticipants'] : array()));
			
			// add author
			$conversationEditor->updateParticipants(array($data['userID']));
			
			// update conversation count
			UserStorageHandler::getInstance()->reset(array($data['userID']), 'conversationCount', PackageDependencyHandler::getInstance()->getPackageID('com.woltlab.wcf.conversation'));
			
			// fire notification event
			$notificationRecipients = array_merge((!empty($this->parameters['participants']) ? $this->parameters['participants'] : array()), (!empty($this->parameters['invisibleParticipants']) ? $this->parameters['invisibleParticipants'] : array()));
			UserNotificationHandler::getInstance()->fireEvent('conversation', 'com.woltlab.wcf.conversation.notification', new ConversationUserNotificationObject($conversation), $notificationRecipients);
		}
		else {
			// update conversation count
			UserStorageHandler::getInstance()->reset($conversation->getParticipantIDs(), 'conversationCount', PackageDependencyHandler::getInstance()->getPackageID('com.woltlab.wcf.conversation'));
		}
		
		// update participant summary
		$conversationEditor->updateParticipantSummary();
		
		// create message
		$data = array(
			'conversationID' => $conversation->conversationID,
			'message' => $this->parameters['messageData']['message'],
			'time' => $this->parameters['data']['time'],
			'userID' => $this->parameters['data']['userID'],
			'username' => $this->parameters['data']['username']
		);
		
		$messageAction = new ConversationMessageAction(array(), 'create', array(
			'data' => $data,
			'conversation' => $conversation,
			'isFirstPost' => true,
			'attachmentHandler' => (isset($this->parameters['attachmentHandler']) ? $this->parameters['attachmentHandler'] : null) 
		));
		$resultValues = $messageAction->executeAction();
		
		// update first message id 
		$conversationEditor->update(array(
			'firstMessageID' => $resultValues['returnValues']->messageID
		));
		
		return $conversation;
	}
	
	/**
	 * @see	wcf\data\AbstractDatabaseObjectAction::update()
	 */
	public function update() {
		// count participants
		if (!empty($this->parameters['participants'])) {
			$this->parameters['data']['participants'] = count($this->parameters['participants']);
		}
		
		parent::update();
		
		foreach ($this->objects as $conversation) {
			// partipants
			if (!empty($this->parameters['participants']) || !empty($this->parameters['invisibleParticipants'])) {
				$conversation->updateParticipants((!empty($this->parameters['participants']) ? $this->parameters['participants'] : array()), (!empty($this->parameters['invisibleParticipants']) ? $this->parameters['invisibleParticipants'] : array()));
				$conversation->updateParticipantSummary();
			}
			
			// draft status
			if (isset($this->parameters['data']['isDraft'])) {
				if ($conversation->isDraft && !$this->parameters['data']['isDraft']) {
					// add author
					$conversation->updateParticipants(array($conversation->userID));
					
					// update conversation count
					UserStorageHandler::getInstance()->reset($conversation->getParticipantIDs(), 'conversationCount', PackageDependencyHandler::getInstance()->getPackageID('com.woltlab.wcf.conversation'));
				}
			}
		}
	}
	
	/**
	 * Marks conversations as read.
	 */
	public function markAsRead() {
		if (empty($this->parameters['visitTime'])) {
			$this->parameters['visitTime'] = TIME_NOW;
		}
		
		if (empty($this->objects)) {
			$this->readObjects();
		}
		
		$sql = "UPDATE	wcf".WCF_N."_conversation_to_user
			SET	lastVisitTime = ?
			WHERE	participantID = ?
				AND conversationID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		WCF::getDB()->beginTransaction();
		foreach ($this->objects as $conversation) {
			$statement->execute(array(
				$this->parameters['visitTime'],
				WCF::getUser()->userID,
				$conversation->conversationID
			));
		}
		WCF::getDB()->commitTransaction();
		
		// reset storage
		UserStorageHandler::getInstance()->reset(array(WCF::getUser()->userID), 'unreadConversationCount', PackageDependencyHandler::getInstance()->getPackageID('com.woltlab.wcf.conversation'));
	}
	
	/**
	 * Validates the mark as read action.
	 */
	public function validateMarkAsRead() {
		// visitTime might not be in the future
		if (isset($this->parameters['visitTime'])) {
			$this->parameters['visitTime'] = intval($this->parameters['visitTime']);
			if ($this->parameters['visitTime'] > TIME_NOW) {
				$this->parameters['visitTime'] = TIME_NOW;
			}
		}
		
		if (empty($this->objects)) {
			$this->readObjects();
		}
		
		// check participation
		$conversationIDs = array();
		foreach ($this->objects as $conversation) {
			$conversationIDs[] = $conversation->conversationID;
		}
		
		if (empty($conversationIDs)) {
			throw new UserInputException('objectIDs');
		}
		
		if (!Conversation::isParticipant($conversationIDs)) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * Validates user access for label management.
	 */
	public function validateGetLabelmanagement() {
		if (!WCF::getSession()->getPermission('user.conversation.canUseConversation')) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * Returns the conversation label management.
	 * 
	 * @return	array
	 */
	public function getLabelManagement() {
		WCF::getTPL()->assign(array(
			'cssClassNames' => ConversationLabel::getLabelCssClassNames(),
			'labelList' => ConversationLabel::getLabelsByUser()
		));
		
		return array(
			'actionName' => 'getLabelManagement',
			'template' => WCF::getTPL()->fetch('conversationLabelManagement')
		);
	}
	
	/**
	 * Validates the get message preview action.
	 */
	public function validateGetMessagePreview() {
		// read data
		if (empty($this->objects)) {
			$this->readObjects();
		}
		// @todo: implement me
	}
	
	/**
	 * Gets a preview of a message in a specific conversation.
	 * 
	 * @return array
	 */
	public function getMessagePreview() {
		$messageList = new ViewableConversationMessageList();
		$conversation = reset($this->objects);
		
		$messageList->getConditionBuilder()->add("conversation_message.messageID = ?", array($conversation->firstMessageID));
		$messageList->readObjects();
		$messages = $messageList->getObjects();
		
		WCF::getTPL()->assign(array(
			'message' => reset($messages)
		));
		return array(
			'template' => WCF::getTPL()->fetch('conversationMessagePreview')
		);
	}
	
	/**
	 * Validates parameters to close conversations.
	 */
	public function validateClose() {
		// read objects
		if (empty($this->objects)) {
			$this->readObjects();
		}
		
		if (empty($this->objects)) {
			throw new ValidateActionException('Invalid object id');
		}
		
		// validate ownership
		foreach ($this->objects as $conversation) {
			if ($conversation->isClosed || ($conversation->userID != WCF::getUser()->userID)) {
				throw new PermissionDeniedException();
			}
		}
	}
	
	/**
	 * Closes conversations.
	 * 
	 * @return	array<array>
	 */
	public function close() {
		foreach ($this->objects as $conversation) {
			// TODO: implement a method 'close()' in order to utilize modification log
			$conversation->update(array('isClosed' => 1));
			$this->addConversationData($conversation->getDecoratedObject(), 'isClosed', 1);
		}
		
		$this->unmarkItems();
		
		return $this->getConversationData();
	}
	
	/**
	 * Validates parameters to open conversations.
	 */
	public function validateOpen() {
		// read objects
		if (empty($this->objects)) {
			$this->readObjects();
		}
	
		if (empty($this->objects)) {
			throw new ValidateActionException('Invalid object id');
		}
	
		// validate ownership
		foreach ($this->objects as $conversation) {
			if (!$conversation->isClosed || ($conversation->userID != WCF::getUser()->userID)) {
				throw new PermissionDeniedException();
			}
		}
	}
	
	/**
	 * Opens conversations.
	 *
	 * @return	array<array>
	 */
	public function open() {
		foreach ($this->objects as $conversation) {
			// TODO: implement a method 'open()' in order to utilize modification log
			$conversation->update(array('isClosed' => 0));
			$this->addConversationData($conversation->getDecoratedObject(), 'isClosed', 0);
		}
		
		$this->unmarkItems();
	
		return $this->getConversationData();
	}
	
	/**
	 * Validates conversations for leave form.
	 */
	public function validateGetLeaveForm() {
		if (empty($this->objectIDs)) {
			throw new UserInputException('objectIDs');
		}
		
		// validate participation
		if (!Conversation::isParticipant($this->objectIDs)) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * Returns dialog form to leave conversations.
	 * 
	 * @return	array
	 */
	public function getLeaveForm() {
		// get hidden state from first conversation (all others have the same state)
		$sql = "SELECT	hideConversation
			FROM	wcf".WCF_N."_conversation_to_user
			WHERE	conversationID = ?
				AND participantID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(
			current($this->objectIDs),
			WCF::getUser()->userID
		));
		$row = $statement->fetchArray();
		
		WCF::getTPL()->assign('hideConversation', $row['hideConversation']);
		
		return array(
			'actionName' => 'getLeaveForm',
			'template' => WCF::getTPL()->fetch('conversationLeave')
		);
	}
	
	/**
	 * Validates parameters to hide conversations.
	 */
	public function validateHideConversation() {
		$this->parameters['hideConversation'] = (isset($this->parameters['hideConversation'])) ? intval($this->parameters['hideConversation']) : null;
		if ($this->parameters['hideConversation'] === null || !in_array($this->parameters['hideConversation'], array(Conversation::STATE_DEFAULT, Conversation::STATE_HIDDEN, Conversation::STATE_LEFT))) {
			throw new UserInputException('hideConversation');
		}
		
		if (empty($this->objectIDs)) {
			throw new UserInputException('objectIDs');
		}
		
		// validate participation
		if (!Conversation::isParticipant($this->objectIDs)) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * Hides or restores conversations.
	 * 
	 * @return	array
	 */
	public function hideConversation() {
		$sql = "UPDATE	wcf".WCF_N."_conversation_to_user
			SET	hideConversation = ?
			WHERE	conversationID = ?
				AND participantID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		
		WCF::getDB()->beginTransaction();
		foreach ($this->objectIDs as $conversationID) {
			$statement->execute(array(
				$this->parameters['hideConversation'],
				$conversationID,
				WCF::getUser()->userID
			));
		}
		WCF::getDB()->commitTransaction();
		
		$this->unmarkItems();
		
		return array(
			'actionName' => 'hideConversation'
		);
	}
	
	/**
	 * Does nothing.
	 */
	public function validateGetUnreadConversations() { }
	
	/**
	 * Returns the last 5 unread conversations.
	 * 
	 * @return	array
	 */
	public function getUnreadConversations() {
		$conversationList = new UserConversationList(WCF::getUser()->userID);
		$conversationList->getConditionBuilder()->add('conversation_to_user.lastVisitTime < conversation.lastPostTime');
		$conversationList->sqlLimit = 5;
		$conversationList->sqlOrderBy = 'conversation.lastPostTime DESC';
		$conversationList->readObjects();
		
		WCF::getTPL()->assign(array(
			'conversations' => $conversationList->getObjects()
		));
		
		return array(
			'template' => WCF::getTPL()->fetch('conversationListUnread')
		);
	}
	
	/**
	 * Does nothing.
	 */
	public function validateUnmarkAll() { }
	
	/**
	 * Unmarks all conversations.
	 */
	public function unmarkAll() {
		ClipboardHandler::getInstance()->removeItems(ClipboardHandler::getInstance()->getObjectTypeID('com.woltlab.wcf.conversation.conversation'));
	}
	
	/**
	 * Validates parameters to display the 'add participants' form.
	 */
	public function validateGetAddParticipantsForm() {
		$this->conversation = $this->getSingleObject();
		if (!Conversation::isParticipant(array($this->conversation->conversationID)) || !$this->conversation->canAddParticipants()) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * Shows the 'add participants' form.
	 * 
	 * @return	array
	 */
	public function getAddParticipantsForm() {
		return array(
			'actionName' => 'getAddParticipantsForm',
			'excludeSearchValues' => $this->conversation->getParticipantNames(),
			'template' => WCF::getTPL()->fetch('conversationAddParticipants')
		);
	}
	
	/**
	 * Validates parameters to add new participants.
	 */
	public function validateAddParticipants() {
		$this->validateGetAddParticipantsForm();
		
		// validate participants
		$this->readString('participants');
	}
	
	/**
	 * Adds new participants.
	 * 
	 * @return	array
	 */
	public function addParticipants() {
		$count = 0;
		$successMessage = '';
		
		$participantIDs = Conversation::validateParticipants($this->parameters['participants'], 'participants', true);
		if (!empty($participantIDs)) {
			// check for already added participants
			$participantIDs = array_diff($participantIDs, $this->conversation->getParticipantIDs());
			if (!empty($participantIDs)) {
				$conversationAction = new ConversationAction(array($this->conversation), 'update', array('participants' => $participantIDs));
				$conversationAction->executeAction();
				
				$count = count($participantIDs);
				$successMessage = WCF::getLanguage()->getDynamicVariable('wcf.conversation.edit.addParticipants.success', array('count' => $count));
			}
		}
		
		return array(
			'actionName' => 'addParticipants',
			'count' => $count,
			'successMessage' => $successMessage
		);
	}
	
	/**
	 * Adds conversation modification data.
	 * 
	 * @param	wcf\data\conversation\Conversation	$conversation
	 * @param	string					$key
	 * @param	mixed					$value
	 */
	protected function addConversationData(Conversation $conversation, $key, $value) {
		if (!isset($this->conversationData[$conversation->conversationID])) {
			$this->conversationData[$conversation->conversationID] = array();
		}
		
		$this->conversationData[$conversation->conversationID][$key] = $value;
	}
	
	/**
	 * Returns thread data.
	 *
	 * @return	array<array>
	 */
	protected function getConversationData() {
		return array(
			'conversationData' => $this->conversationData
		);
	}
	
	/**
	 * Unmarks conversations.
	 * 
	 * @param	array<integer>		$conversationIDs
	 */
	protected function unmarkItems(array $conversationIDs = array()) {
		if (empty($conversationIDs)) {
			$conversationIDs = $this->objectIDs;
		}
		
		ClipboardHandler::getInstance()->unmark($conversationIDs, ClipboardHandler::getInstance()->getObjectTypeID('com.woltlab.wcf.conversation.conversation'));
	}
}
