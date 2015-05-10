<?php
namespace wcf\data\conversation;
use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\message\ConversationMessageAction;
use wcf\data\conversation\message\ConversationMessageList;
use wcf\data\conversation\message\SimplifiedViewableConversationMessageList;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\IClipboardAction;
use wcf\data\IVisitableObjectAction;
use wcf\system\clipboard\ClipboardHandler;
use wcf\system\conversation\ConversationHandler;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\log\modification\ConversationModificationLogHandler;
use wcf\system\request\LinkHandler;
use wcf\system\user\notification\object\ConversationUserNotificationObject;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;

/**
 * Executes conversation-related actions.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2015 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation
 * @category	Community Framework
 */
class ConversationAction extends AbstractDatabaseObjectAction implements IClipboardAction, IVisitableObjectAction {
	/**
	 * @see	\wcf\data\AbstractDatabaseObjectAction::$className
	 */
	protected $className = 'wcf\data\conversation\ConversationEditor';
	
	/**
	 * conversation object
	 * @var	\wcf\data\conversation\ConversationEditor
	 */
	protected $conversation = null;
	
	/**
	 * list of conversation data modifications
	 * @var	array<array>
	 */
	protected $conversationData = array();
	
	/**
	 * @see	\wcf\data\AbstractDatabaseObjectAction::create()
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
		
		if (!$conversation->isDraft) {
			// save participants
			$conversationEditor->updateParticipants((!empty($this->parameters['participants']) ? $this->parameters['participants'] : array()), (!empty($this->parameters['invisibleParticipants']) ? $this->parameters['invisibleParticipants'] : array()));
			
			// add author
			$conversationEditor->updateParticipants(array($data['userID']));
			
			// update conversation count
			UserStorageHandler::getInstance()->reset($conversation->getParticipantIDs(), 'conversationCount');
			
			// mark conversation as read for the author
			$sql = "UPDATE	wcf".WCF_N."_conversation_to_user
				SET	lastVisitTime = ?
				WHERE	participantID = ?
					AND conversationID = ?";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute(array($data['time'], $data['userID'], $conversation->conversationID));
		}
		else {
			// update conversation count
			UserStorageHandler::getInstance()->reset(array($data['userID']), 'conversationCount');
		}
		
		// update participant summary
		$conversationEditor->updateParticipantSummary();
		
		// create message
		$messageData = $this->parameters['messageData'];
		$messageData['conversationID'] = $conversation->conversationID;
		$messageData['time'] = $this->parameters['data']['time'];
		$messageData['userID'] = $this->parameters['data']['userID'];
		$messageData['username'] = $this->parameters['data']['username'];
		
		$messageAction = new ConversationMessageAction(array(), 'create', array(
			'data' => $messageData,
			'conversation' => $conversation,
			'isFirstPost' => true,
			'attachmentHandler' => (isset($this->parameters['attachmentHandler']) ? $this->parameters['attachmentHandler'] : null) 
		));
		$resultValues = $messageAction->executeAction();
		
		// update first message id
		$conversationEditor->update(array(
			'firstMessageID' => $resultValues['returnValues']->messageID
		));
		
		$conversation->setFirstMessage($resultValues['returnValues']);
		if (!$conversation->isDraft) {
			// fire notification event
			$notificationRecipients = array_merge((!empty($this->parameters['participants']) ? $this->parameters['participants'] : array()), (!empty($this->parameters['invisibleParticipants']) ? $this->parameters['invisibleParticipants'] : array()));
			UserNotificationHandler::getInstance()->fireEvent('conversation', 'com.woltlab.wcf.conversation.notification', new ConversationUserNotificationObject($conversation), $notificationRecipients);
		}
		
		return $conversation;
	}
	
	/**
	 * @see	\wcf\data\IDeleteAction::delete()
	 */
	public function delete() {
		// deletes messages
		$messageList = new ConversationMessageList();
		$messageList->getConditionBuilder()->add('conversation_message.conversationID IN (?)', array($this->objectIDs));
		$messageList->readObjectIDs();
		$action = new ConversationMessageAction($messageList->getObjectIDs(), 'delete');
		$action->executeAction();
		
		// delete conversations
		parent::delete();
		
		if (!empty($this->objectIDs)) {
			// delete notifications
			UserNotificationHandler::getInstance()->deleteNotifications('conversation', 'com.woltlab.wcf.conversation.notification', array(), $this->objectIDs);
			
			// remove modification logs
			ConversationModificationLogHandler::getInstance()->remove($this->objectIDs);
		}
	}
	
	/**
	 * @see	\wcf\data\AbstractDatabaseObjectAction::update()
	 */
	public function update() {
		if (!isset($this->parameters['participants'])) $this->parameters['participants'] = array();
		if (!isset($this->parameters['invisibleParticipants'])) $this->parameters['invisibleParticipants'] = array();
		
		// count participants
		if (!empty($this->parameters['participants'])) {
			$this->parameters['data']['participants'] = count($this->parameters['participants']);
		}
		
		parent::update();
		
		foreach ($this->objects as $conversation) {
			// partipants
			if (!empty($this->parameters['participants']) || !empty($this->parameters['invisibleParticipants'])) {
				// get current participants
				$participantIDs = $conversation->getParticipantIDs();
				
				$conversation->updateParticipants((!empty($this->parameters['participants']) ? $this->parameters['participants'] : array()), (!empty($this->parameters['invisibleParticipants']) ? $this->parameters['invisibleParticipants'] : array()));
				$conversation->updateParticipantSummary();
				
				// check if new participants have been added
				$newParticipantIDs = array_diff(array_merge($this->parameters['participants'], $this->parameters['invisibleParticipants']), $participantIDs);
				if (!empty($newParticipantIDs)) {
					// update conversation count
					UserStorageHandler::getInstance()->reset($newParticipantIDs, 'unreadConversationCount');
					UserStorageHandler::getInstance()->reset($newParticipantIDs, 'conversationCount');
					
					// fire notification event
					UserNotificationHandler::getInstance()->fireEvent('conversation', 'com.woltlab.wcf.conversation.notification', new ConversationUserNotificationObject($conversation->getDecoratedObject()), $newParticipantIDs);
				}
			}
			
			// draft status
			if (isset($this->parameters['data']['isDraft'])) {
				if ($conversation->isDraft && !$this->parameters['data']['isDraft']) {
					// add author
					$conversation->updateParticipants(array($conversation->userID));
					
					// update conversation count
					UserStorageHandler::getInstance()->reset($conversation->getParticipantIDs(), 'unreadConversationCount');
					UserStorageHandler::getInstance()->reset($conversation->getParticipantIDs(), 'conversationCount');
				}
			}
		}
	}
	
	/**
	 * @see	\wcf\data\IVisitableObjectAction::markAsRead()
	 */
	public function markAsRead() {
		if (empty($this->parameters['visitTime'])) {
			$this->parameters['visitTime'] = TIME_NOW;
		}
		
		if (empty($this->objects)) {
			$this->readObjects();
		}
		
		$conversationIDs = array();
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
			$conversationIDs[] = $conversation->conversationID;
		}
		WCF::getDB()->commitTransaction();
		
		// reset storage
		UserStorageHandler::getInstance()->reset(array(WCF::getUser()->userID), 'unreadConversationCount');
		
		// mark notifications as confirmed
		if (!empty($conversationIDs)) {
			// conversation start notification
			$conditionBuilder = new PreparedStatementConditionBuilder();
			$conditionBuilder->add('notification.eventID = ?', array(UserNotificationHandler::getInstance()->getEvent('com.woltlab.wcf.conversation.notification', 'conversation')->eventID));
			$conditionBuilder->add('notification.objectID = conversation.conversationID');
			$conditionBuilder->add('notification.userID = ?', array(WCF::getUser()->userID));
			$conditionBuilder->add('conversation.conversationID IN (?)', array($conversationIDs));
			$conditionBuilder->add('conversation.time <= ?', array($this->parameters['visitTime']));
			
			$sql = "SELECT		conversation.conversationID
				FROM		wcf".WCF_N."_conversation conversation,
						wcf".WCF_N."_user_notification notification
				".$conditionBuilder;
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute($conditionBuilder->getParameters());
			$notificationObjectIDs = array();
			while ($row = $statement->fetchArray()) {
				$notificationObjectIDs[] = $row['conversationID'];
			}
			
			if (!empty($notificationObjectIDs)) {
				UserNotificationHandler::getInstance()->markAsConfirmed('conversation', 'com.woltlab.wcf.conversation.notification', array(WCF::getUser()->userID), $notificationObjectIDs);
			}
			
			// conversation reply notification
			$conditionBuilder = new PreparedStatementConditionBuilder();
			$conditionBuilder->add('notification.eventID = ?', array(UserNotificationHandler::getInstance()->getEvent('com.woltlab.wcf.conversation.message.notification', 'conversationMessage')->eventID));
			$conditionBuilder->add('notification.objectID = conversation_message.messageID');
			$conditionBuilder->add('notification.userID = ?', array(WCF::getUser()->userID));
			$conditionBuilder->add('conversation_message.conversationID IN (?)', array($conversationIDs));
			$conditionBuilder->add('conversation_message.time <= ?', array($this->parameters['visitTime']));
			
			$sql = "SELECT		conversation_message.messageID
				FROM		wcf".WCF_N."_conversation_message conversation_message,
						wcf".WCF_N."_user_notification notification
				".$conditionBuilder;
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute($conditionBuilder->getParameters());
			$notificationObjectIDs = array();
			while ($row = $statement->fetchArray()) {
				$notificationObjectIDs[] = $row['messageID'];
			}
			
			if (!empty($notificationObjectIDs)) {
				UserNotificationHandler::getInstance()->markAsConfirmed('conversationMessage', 'com.woltlab.wcf.conversation.message.notification', array(WCF::getUser()->userID), $notificationObjectIDs);
			}
		}
		
		if (!empty($conversationIDs)) {
			$this->unmarkItems($conversationIDs);
		}
		
		$returnValues = array(
			'totalCount' => ConversationHandler::getInstance()->getUnreadConversationCount(null, true)
		);
		
		if (count($conversationIDs) == 1) {
			$returnValues['markAsRead'] = reset($conversationIDs);
		}
		
		return $returnValues;
	}
	
	/**
	 * @see	\wcf\data\IVisitableObjectAction::validateMarkAsRead()
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
	 * Marks all conversations as read.
	 */
	public function markAllAsRead() {
		$sql = "UPDATE	wcf".WCF_N."_conversation_to_user
			SET	lastVisitTime = ?
			WHERE	participantID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(
			TIME_NOW,
			WCF::getUser()->userID
		));
		
		// reset storage
		UserStorageHandler::getInstance()->reset(array(WCF::getUser()->userID), 'unreadConversationCount');
		
		// delete obsolete notifications
		UserNotificationHandler::getInstance()->deleteNotifications('conversation', 'com.woltlab.wcf.conversation.notification', array(WCF::getUser()->userID));
		UserNotificationHandler::getInstance()->deleteNotifications('conversationMessage', 'com.woltlab.wcf.conversation.message.notification', array(WCF::getUser()->userID));
		
		return array(
			'markAllAsRead' => true
		);
	}
	
	/**
	 * Validates the markAllAsRead action.
	 */
	public function validateMarkAllAsRead() {}
	
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
			'template' => WCF::getTPL()->fetch('conversationLabelManagement'),
			'maxLabels' => WCF::getSession()->getPermission('user.conversation.maxLabels'),
			'labelCount' => count(ConversationLabel::getLabelsByUser())
		);
	}
	
	/**
	 * Validates the get message preview action.
	 */
	public function validateGetMessagePreview() {
		$this->conversation = $this->getSingleObject();
		if (!Conversation::isParticipant(array($this->conversation->conversationID))) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * Returns a preview of a message in a specific conversation.
	 * 
	 * @return	array
	 */
	public function getMessagePreview() {
		$messageList = new SimplifiedViewableConversationMessageList();
		
		$messageList->getConditionBuilder()->add("conversation_message.messageID = ?", array($this->conversation->firstMessageID));
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
			
			if (empty($this->objects)) {
				throw new UserInputException('objectIDs');
			}
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
			$conversation->update(array('isClosed' => 1));
			$this->addConversationData($conversation->getDecoratedObject(), 'isClosed', 1);
			
			ConversationModificationLogHandler::getInstance()->close($conversation->getDecoratedObject());
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
			
			if (empty($this->objects)) {
				throw new UserInputException('objectIDs');
			}
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
			$conversation->update(array('isClosed' => 0));
			$this->addConversationData($conversation->getDecoratedObject(), 'isClosed', 0);
			
			ConversationModificationLogHandler::getInstance()->open($conversation->getDecoratedObject());
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
		
		// reset user's conversation counters if user leaves conversation
		// permanently
		if ($this->parameters['hideConversation'] == Conversation::STATE_LEFT) {
			UserStorageHandler::getInstance()->reset(array(WCF::getUser()->userID), 'conversationCount');
			UserStorageHandler::getInstance()->reset(array(WCF::getUser()->userID), 'unreadConversationCount');
		}
		
		// add modification log entry
		if ($this->parameters['hideConversation'] == Conversation::STATE_LEFT) {
			if (empty($this->objects)) $this->readObjects();
			
			foreach ($this->objects as $conversation) {
				ConversationModificationLogHandler::getInstance()->leave($conversation->getDecoratedObject());
			}
		}
		
		// unmark items
		$this->unmarkItems();
		
		if ($this->parameters['hideConversation'] == Conversation::STATE_LEFT) {
			// update participants count and participant summary
			ConversationEditor::updateParticipantCounts($this->objectIDs);
			ConversationEditor::updateParticipantSummaries($this->objectIDs);
			
			// delete conversation if all users have left it
			$conditionBuilder = new PreparedStatementConditionBuilder();
			$conditionBuilder->add('conversation.conversationID IN (?)', array($this->objectIDs));
			$conditionBuilder->add('conversation_to_user.conversationID IS NULL');
			$conversationIDs = array();
			$sql = "SELECT		DISTINCT conversation.conversationID
				FROM		wcf".WCF_N."_conversation conversation
				LEFT JOIN	wcf".WCF_N."_conversation_to_user conversation_to_user
				ON		(	conversation_to_user.conversationID = conversation.conversationID
						AND	conversation_to_user.hideConversation <> ".Conversation::STATE_LEFT."
						AND	conversation_to_user.participantID IS NOT NULL)
				".$conditionBuilder;
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute($conditionBuilder->getParameters());
			while ($row = $statement->fetchArray()) {
				$conversationIDs[] = $row['conversationID'];
			}
			if (!empty($conversationIDs)) {
				$action = new ConversationAction($conversationIDs, 'delete');
				$action->executeAction();
			}
		}
		
		return array(
			'actionName' => 'hideConversation',
			'redirectURL' => LinkHandler::getInstance()->getLink('ConversationList')
		);
	}
	
	/**
	 * Validates parameters to return the mixed conversation list.
	 */
	public function validateGetMixedConversationList() {
		// does nothing
	}
	
	/**
	 * Returns a mixed conversation list with up to 5 unread conversations.
	 * 
	 * @return	array<mixed>
	 */
	public function getMixedConversationList() {
		$unreadConversationList = new UserConversationList(WCF::getUser()->userID);
		$unreadConversationList->getConditionBuilder()->add('conversation_to_user.lastVisitTime < conversation.lastPostTime');
		$unreadConversationList->sqlLimit = 5;
		$unreadConversationList->sqlOrderBy = 'conversation.lastPostTime DESC';
		$unreadConversationList->readObjects();
		
		$conversations = array();
		$count = 0;
		foreach ($unreadConversationList as $conversation) {
			$conversations[] = $conversation;
			$count++;
		}
		
		if ($count < 5) {
			$conversationList = new UserConversationList(WCF::getUser()->userID);
			$conversationList->getConditionBuilder()->add('conversation_to_user.lastVisitTime >= conversation.lastPostTime');
			$conversationList->sqlLimit = (5 - $count);
			$conversationList->sqlOrderBy = 'conversation.lastPostTime DESC';
			$conversationList->readObjects();
			
			foreach ($conversationList as $conversation) {
				$conversations[] = $conversation;
			}
		}
		
		WCF::getTPL()->assign(array(
			'conversations' => $conversations
		));
		
		$totalCount = ConversationHandler::getInstance()->getUnreadConversationCount();
		if ($count < 5 && $count < $totalCount) {
			UserStorageHandler::getInstance()->reset(array(WCF::getUser()->userID), 'unreadConversationCount');
		}
		
		return array(
			'template' => WCF::getTPL()->fetch('conversationListUserPanel'),
			'totalCount' => $totalCount
		);
	}
	
	/**
	 * Validates the 'unmarkAll' action.
	 */
	public function validateUnmarkAll() {
		// does nothing
	}
	
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
		try {
			$participantIDs = Conversation::validateParticipants($this->parameters['participants'], 'participants', $this->conversation->getParticipantIDs(true));
		}
		catch (UserInputException $e) {
			$errorMessage = '';
			foreach ($e->getType() as $type) {
				if (!empty($errorMessage)) $errorMessage .= ' ';
				$errorMessage .= WCF::getLanguage()->getDynamicVariable('wcf.conversation.participants.error.'.$type['type'], array('errorData' => array('username' => $type['username'])));
			}
			
			return array(
				'actionName' => 'addParticipants',
				'errorMessage' => $errorMessage
			);
		}
		
		// validate limit
		$newCount = $this->conversation->participants + count($participantIDs);
		if ($newCount > WCF::getSession()->getPermission('user.conversation.maxParticipants')) {
			return array(
				'actionName' => 'addParticipants',
				'errorMessage' => WCF::getLanguage()->getDynamicVariable('wcf.conversation.participants.error.tooManyParticipants')
			);
		}
		
		$count = 0;
		$successMessage = '';
		if (!empty($participantIDs)) {
			// check for already added participants
			$data = array();
			if ($this->conversation->isDraft) {
				$draftData = unserialize($this->conversation->draftData);
				$draftData['participants'] = array_merge($draftData['participants'], $participantIDs);
				$data = array('data' => array('draftData' => serialize($draftData)));
			}
			else {
				$data = array('participants' => $participantIDs);
			}
			
			$conversationAction = new ConversationAction(array($this->conversation), 'update', $data);
			$conversationAction->executeAction();
			
			$count = count($participantIDs);
			$successMessage = WCF::getLanguage()->getDynamicVariable('wcf.conversation.edit.addParticipants.success', array('count' => $count));
			
			ConversationModificationLogHandler::getInstance()->addParticipants($this->conversation->getDecoratedObject(), $participantIDs);
			
			if (!$this->conversation->isDraft) {
				// update participant summary
				$this->conversation->updateParticipantSummary();
			}
		}
		
		return array(
			'actionName' => 'addParticipants',
			'count' => $count,
			'successMessage' => $successMessage
		);
	}
	
	/**
	 * Validates parameters to remove a participant from a conversation.
	 */
	public function validateRemoveParticipant() {
		$this->readInteger('userID');
		
		// validate conversation
		$this->conversation = $this->getSingleObject();
		if (!$this->conversation->conversationID) {
			throw new UserInputException('objectIDs');
		}
		
		// check ownership
		if ($this->conversation->userID != WCF::getUser()->userID) {
			throw new PermissionDeniedException();
		}
		
		// validate participants
		if ($this->parameters['userID'] == WCF::getUser()->userID || !Conversation::isParticipant(array($this->conversation->conversationID)) || !Conversation::isParticipant(array($this->conversation->conversationID), $this->parameters['userID'])) {
			throw new PermissionDeniedException();
		}
		
	}
	
	/**
	 * Removes a participant from a conversation.
	 */
	public function removeParticipant() {
		$this->conversation->removeParticipant($this->parameters['userID']);
		$this->conversation->updateParticipantSummary();
		
		ConversationModificationLogHandler::getInstance()->removeParticipant($this->conversation->getDecoratedObject(), $this->parameters['userID']);
		
		// reset storage
		UserStorageHandler::getInstance()->reset(array($this->parameters['userID']), 'unreadConversationCount');
		
		return array(
			'userID' => $this->parameters['userID']
		);
	}
	
	/**
	 * Rebuilds the conversation data of the relevant conversations.
	 */
	public function rebuild() {
		if (empty($this->objects)) {
			$this->readObjects();
		}
		
		// collect number of messages for each conversation
		$conditionBuilder = new PreparedStatementConditionBuilder();
		$conditionBuilder->add('conversation_message.conversationID IN (?)', array($this->objectIDs));
		$sql = "SELECT		conversationID, COUNT(messageID) AS messages, SUM(attachments) AS attachments
			FROM		wcf".WCF_N."_conversation_message conversation_message
			".$conditionBuilder."
			GROUP BY	conversationID";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditionBuilder->getParameters());
		
		$objectIDs = array();
		while ($row = $statement->fetchArray()) {
			if (!$row['messages']) {
				continue;
			}
			$objectIDs[] = $row['conversationID'];
			
			$conversationEditor = new ConversationEditor(new Conversation(null, array(
				'conversationID' => $row['conversationID']
			)));
			$conversationEditor->update(array(
				'attachments' => $row['attachments'],
				'replies' => $row['messages'] - 1
			));
			$conversationEditor->updateFirstMessage();
			$conversationEditor->updateLastMessage();
		}
		
		// delete conversations without messages
		$deleteConversationIDs = array_diff($this->objectIDs, $objectIDs);
		if (!empty($deleteConversationIDs)) {
			$conversationAction = new ConversationAction($deleteConversationIDs, 'delete');
			$conversationAction->executeAction();
		}
	}
	
	/**
	 * Adds conversation modification data.
	 * 
	 * @param	\wcf\data\conversation\Conversation	$conversation
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
	 * Returns conversation data.
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
