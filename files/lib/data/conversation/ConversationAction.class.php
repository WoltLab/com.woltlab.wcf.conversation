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
 * @copyright	2001-2018 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\Data\Conversation
 * 
 * @method	ConversationEditor[]	getObjects()
 * @method	ConversationEditor	getSingleObject()
 */
class ConversationAction extends AbstractDatabaseObjectAction implements IClipboardAction, IVisitableObjectAction {
	/**
	 * @inheritDoc
	 */
	protected $className = ConversationEditor::class;
	
	/**
	 * conversation object
	 * @var	ConversationEditor
	 */
	protected $conversation;
	
	/**
	 * list of conversation data modifications
	 * @var	mixed[][]
	 */
	protected $conversationData = [];
	
	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * @inheritDoc
	 * @return	Conversation
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
		$conversation = call_user_func([$this->className, 'create'], $data);
		$conversationEditor = new ConversationEditor($conversation);
		
		if (!$conversation->isDraft) {
			// save participants
			$conversationEditor->updateParticipants(
				(!empty($this->parameters['participants']) ? $this->parameters['participants'] : []),
				(!empty($this->parameters['invisibleParticipants']) ? $this->parameters['invisibleParticipants'] : []),
				'all'
			);
			
			// add author
			$conversationEditor->updateParticipants([$data['userID']], [], 'all');
			
			// update conversation count
			UserStorageHandler::getInstance()->reset($conversation->getParticipantIDs(), 'conversationCount');
			
			// mark conversation as read for the author
			$sql = "UPDATE	wcf".WCF_N."_conversation_to_user
				SET	lastVisitTime = ?
				WHERE	participantID = ?
					AND conversationID = ?";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute([$data['time'], $data['userID'], $conversation->conversationID]);
		}
		else {
			// update conversation count
			UserStorageHandler::getInstance()->reset([$data['userID']], 'conversationCount');
		}
		
		// update participant summary
		$conversationEditor->updateParticipantSummary();
		
		// create message
		$messageData = $this->parameters['messageData'];
		$messageData['conversationID'] = $conversation->conversationID;
		$messageData['time'] = $this->parameters['data']['time'];
		$messageData['userID'] = $this->parameters['data']['userID'];
		$messageData['username'] = $this->parameters['data']['username'];
		
		$messageAction = new ConversationMessageAction([], 'create', [
			'data' => $messageData,
			'conversation' => $conversation,
			'isFirstPost' => true,
			'attachmentHandler' => isset($this->parameters['attachmentHandler']) ? $this->parameters['attachmentHandler'] : null,
			'htmlInputProcessor' => isset($this->parameters['htmlInputProcessor']) ? $this->parameters['htmlInputProcessor'] : null
		]);
		$resultValues = $messageAction->executeAction();
		
		// update first message id
		$conversationEditor->update([
			'firstMessageID' => $resultValues['returnValues']->messageID
		]);
		
		$conversation->setFirstMessage($resultValues['returnValues']);
		if (!$conversation->isDraft) {
			// fire notification event
			$notificationRecipients = array_merge((!empty($this->parameters['participants']) ? $this->parameters['participants'] : []), (!empty($this->parameters['invisibleParticipants']) ? $this->parameters['invisibleParticipants'] : []));
			UserNotificationHandler::getInstance()->fireEvent(
				'conversation',
				'com.woltlab.wcf.conversation.notification',
				new ConversationUserNotificationObject($conversation),
				$notificationRecipients
			);
		}
		
		return $conversation;
	}
	
	/**
	 * @inheritDoc
	 */
	public function delete() {
		// deletes messages
		$messageList = new ConversationMessageList();
		$messageList->getConditionBuilder()->add('conversation_message.conversationID IN (?)', [$this->objectIDs]);
		$messageList->readObjectIDs();
		$action = new ConversationMessageAction($messageList->getObjectIDs(), 'delete');
		$action->executeAction();
		
		// get the list of participants in order to reset the 'unread conversation'-counter
		$participantIDs = [];
		if (!empty($this->objectIDs)) {
			$conditions = new PreparedStatementConditionBuilder();
			$conditions->add("conversationID IN (?)", [$this->objectIDs]);
			$sql = "SELECT  DISTINCT participantID
			FROM    wcf" . WCF_N . "_conversation_to_user
			" . $conditions;
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute($conditions->getParameters());
			
			while ($participantID = $statement->fetchColumn()) {
				$participantIDs[] = $participantID;
			}
		}
		
		// delete conversations
		parent::delete();
		
		if (!empty($this->objectIDs)) {
			// delete notifications
			UserNotificationHandler::getInstance()->removeNotifications('com.woltlab.wcf.conversation.notification', $this->objectIDs);
			
			// remove modification logs
			ConversationModificationLogHandler::getInstance()->deleteLogs($this->objectIDs);
			
			// reset the number of unread conversations
			if (!empty($participantIDs)) {
				UserStorageHandler::getInstance()->reset($participantIDs, 'unreadConversationCount');
			}
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function update() {
		if (!isset($this->parameters['participants'])) $this->parameters['participants'] = [];
		if (!isset($this->parameters['invisibleParticipants'])) $this->parameters['invisibleParticipants'] = [];
		
		// count participants
		if (!empty($this->parameters['participants'])) {
			$this->parameters['data']['participants'] = count($this->parameters['participants']);
		}
		
		parent::update();
		
		foreach ($this->getObjects() as $conversation) {
			// participants
			if (!empty($this->parameters['participants']) || !empty($this->parameters['invisibleParticipants'])) {
				// get current participants
				$participantIDs = $conversation->getParticipantIDs();
				
				$conversation->updateParticipants(
					(!empty($this->parameters['participants']) ? $this->parameters['participants'] : []),
					(!empty($this->parameters['invisibleParticipants']) ? $this->parameters['invisibleParticipants'] : []),
					(!empty($this->parameters['visibility']) ? $this->parameters['visibility'] : 'all')
				);
				$conversation->updateParticipantSummary();
				
				// check if new participants have been added
				$newParticipantIDs = array_diff(array_merge($this->parameters['participants'], $this->parameters['invisibleParticipants']), $participantIDs);
				if (!empty($newParticipantIDs)) {
					// update conversation count
					UserStorageHandler::getInstance()->reset($newParticipantIDs, 'unreadConversationCount');
					UserStorageHandler::getInstance()->reset($newParticipantIDs, 'conversationCount');
					
					// fire notification event
					UserNotificationHandler::getInstance()->fireEvent(
						'conversation',
						'com.woltlab.wcf.conversation.notification',
						new ConversationUserNotificationObject($conversation->getDecoratedObject()),
						$newParticipantIDs
					);
				}
			}
			
			// draft status
			if (isset($this->parameters['data']['isDraft'])) {
				if ($conversation->isDraft && !$this->parameters['data']['isDraft']) {
					// add author
					$conversation->updateParticipants([$conversation->userID], [], 'all');
					
					// update conversation count
					UserStorageHandler::getInstance()->reset($conversation->getParticipantIDs(), 'unreadConversationCount');
					UserStorageHandler::getInstance()->reset($conversation->getParticipantIDs(), 'conversationCount');
				}
			}
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function markAsRead() {
		if (empty($this->parameters['visitTime'])) {
			$this->parameters['visitTime'] = TIME_NOW;
		}

		// in case this is a call via PHP and the userID parameter is missing, set it to the userID of the current user
		if (!isset($this->parameters['userID'])) {
			$this->parameters['userID'] = WCF::getUser()->userID;
		}
		
		if (empty($this->objects)) {
			$this->readObjects();
		}
		
		$conversationIDs = [];
		$sql = "UPDATE	wcf".WCF_N."_conversation_to_user
			SET	lastVisitTime = ?
			WHERE	participantID = ?
				AND conversationID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		WCF::getDB()->beginTransaction();
		foreach ($this->getObjects() as $conversation) {
			$statement->execute([
				$this->parameters['visitTime'],
				$this->parameters['userID'],
				$conversation->conversationID
			]);
			$conversationIDs[] = $conversation->conversationID;
		}
		WCF::getDB()->commitTransaction();
		
		// reset storage
		UserStorageHandler::getInstance()->reset([$this->parameters['userID']], 'unreadConversationCount');
		
		// mark notifications as confirmed
		if (!empty($conversationIDs)) {
			// conversation start notification
			$conditionBuilder = new PreparedStatementConditionBuilder();
			$conditionBuilder->add('notification.eventID = ?', [UserNotificationHandler::getInstance()->getEvent('com.woltlab.wcf.conversation.notification', 'conversation')->eventID]);
			$conditionBuilder->add('notification.objectID = conversation.conversationID');
			$conditionBuilder->add('notification.userID = ?', [$this->parameters['userID']]);
			$conditionBuilder->add('conversation.conversationID IN (?)', [$conversationIDs]);
			$conditionBuilder->add('conversation.time <= ?', [$this->parameters['visitTime']]);
			
			$sql = "SELECT		conversation.conversationID
				FROM		wcf".WCF_N."_conversation conversation,
						wcf".WCF_N."_user_notification notification
				".$conditionBuilder;
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute($conditionBuilder->getParameters());
			$notificationObjectIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);
			
			if (!empty($notificationObjectIDs)) {
				UserNotificationHandler::getInstance()->markAsConfirmed('conversation', 'com.woltlab.wcf.conversation.notification', [$this->parameters['userID']], $notificationObjectIDs);
			}
			
			// conversation reply notification
			$conditionBuilder = new PreparedStatementConditionBuilder();
			$conditionBuilder->add('notification.eventID = ?', [UserNotificationHandler::getInstance()->getEvent('com.woltlab.wcf.conversation.message.notification', 'conversationMessage')->eventID]);
			$conditionBuilder->add('notification.objectID = conversation_message.messageID');
			$conditionBuilder->add('notification.userID = ?', [$this->parameters['userID']]);
			$conditionBuilder->add('conversation_message.conversationID IN (?)', [$conversationIDs]);
			$conditionBuilder->add('conversation_message.time <= ?', [$this->parameters['visitTime']]);
			
			$sql = "SELECT		conversation_message.messageID
				FROM		wcf".WCF_N."_conversation_message conversation_message,
						wcf".WCF_N."_user_notification notification
				".$conditionBuilder;
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute($conditionBuilder->getParameters());
			$notificationObjectIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);
			
			if (!empty($notificationObjectIDs)) {
				UserNotificationHandler::getInstance()->markAsConfirmed('conversationMessage', 'com.woltlab.wcf.conversation.message.notification', [$this->parameters['userID']], $notificationObjectIDs);
			}
		}
		
		if (!empty($conversationIDs)) {
			$this->unmarkItems($conversationIDs);
		}
		
		$returnValues = [
			'totalCount' => ConversationHandler::getInstance()->getUnreadConversationCount(null, true)
		];
		
		if (count($conversationIDs) == 1) {
			$returnValues['markAsRead'] = reset($conversationIDs);
		}
		
		return $returnValues;
	}
	
	/**
	 * @inheritDoc
	 */
	public function validateMarkAsRead() {
		// visitTime might not be in the future
		if (isset($this->parameters['visitTime'])) {
			$this->parameters['visitTime'] = intval($this->parameters['visitTime']);
			if ($this->parameters['visitTime'] > TIME_NOW) {
				$this->parameters['visitTime'] = TIME_NOW;
			}
		}

		// userID should always be equal to the userID of the current user when called via AJAX
		$this->parameters['userID'] = WCF::getUser()->userID;
		
		if (empty($this->objects)) {
			$this->readObjects();
		}
		
		// check participation
		$conversationIDs = [];
		foreach ($this->getObjects() as $conversation) {
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
		$statement->execute([
			TIME_NOW,
			WCF::getUser()->userID
		]);
		
		// reset storage
		UserStorageHandler::getInstance()->reset([WCF::getUser()->userID], 'unreadConversationCount');
		
		// confirm obsolete notifications
		UserNotificationHandler::getInstance()->markAsConfirmed('conversation', 'com.woltlab.wcf.conversation.notification', [WCF::getUser()->userID]);
		UserNotificationHandler::getInstance()->markAsConfirmed('conversationMessage', 'com.woltlab.wcf.conversation.message.notification', [WCF::getUser()->userID]);
		
		return [
			'markAllAsRead' => true
		];
	}
	
	/**
	 * Validates the markAllAsRead action.
	 */
	public function validateMarkAllAsRead() {
		// does nothing
	}
	
	/**
	 * Validates user access for label management.
	 * 
	 * @throws	PermissionDeniedException
	 */
	public function validateGetLabelManagement() {
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
		WCF::getTPL()->assign([
			'cssClassNames' => ConversationLabel::getLabelCssClassNames(),
			'labelList' => ConversationLabel::getLabelsByUser()
		]);
		
		return [
			'actionName' => 'getLabelManagement',
			'template' => WCF::getTPL()->fetch('conversationLabelManagement'),
			'maxLabels' => WCF::getSession()->getPermission('user.conversation.maxLabels'),
			'labelCount' => count(ConversationLabel::getLabelsByUser())
		];
	}
	
	/**
	 * Validates the get message preview action.
	 * 
	 * @throws	PermissionDeniedException
	 */
	public function validateGetMessagePreview() {
		$this->conversation = $this->getSingleObject();
		if (!Conversation::isParticipant([$this->conversation->conversationID])) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * Returns a preview of a message in a specific conversation.
	 * 
	 * @return	string[]
	 */
	public function getMessagePreview() {
		$messageList = new SimplifiedViewableConversationMessageList();
		
		$messageList->getConditionBuilder()->add("conversation_message.messageID = ?", [$this->conversation->firstMessageID]);
		$messageList->readObjects();
		$messages = $messageList->getObjects();
		
		WCF::getTPL()->assign([
			'message' => reset($messages)
		]);
		return [
			'template' => WCF::getTPL()->fetch('conversationMessagePreview')
		];
	}
	
	/**
	 * Validates parameters to close conversations.
	 * 
	 * @throws	PermissionDeniedException
	 * @throws	UserInputException
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
		foreach ($this->getObjects() as $conversation) {
			if ($conversation->isClosed || ($conversation->userID != WCF::getUser()->userID)) {
				throw new PermissionDeniedException();
			}
		}
	}
	
	/**
	 * Closes conversations.
	 * 
	 * @return	mixed[][]
	 */
	public function close() {
		foreach ($this->getObjects() as $conversation) {
			$conversation->update(['isClosed' => 1]);
			$this->addConversationData($conversation->getDecoratedObject(), 'isClosed', 1);
			
			ConversationModificationLogHandler::getInstance()->close($conversation->getDecoratedObject());
		}
		
		$this->unmarkItems();
		
		return $this->getConversationData();
	}
	
	/**
	 * Validates parameters to open conversations.
	 * 
	 * @throws	PermissionDeniedException
	 * @throws	UserInputException
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
		foreach ($this->getObjects() as $conversation) {
			if (!$conversation->isClosed || ($conversation->userID != WCF::getUser()->userID)) {
				throw new PermissionDeniedException();
			}
		}
	}
	
	/**
	 * Opens conversations.
	 * 
	 * @return	mixed[][]
	 */
	public function open() {
		foreach ($this->getObjects() as $conversation) {
			$conversation->update(['isClosed' => 0]);
			$this->addConversationData($conversation->getDecoratedObject(), 'isClosed', 0);
			
			ConversationModificationLogHandler::getInstance()->open($conversation->getDecoratedObject());
		}
		
		$this->unmarkItems();
		
		return $this->getConversationData();
	}
	
	/**
	 * Validates conversations for leave form.
	 * 
	 * @throws	PermissionDeniedException
	 * @throws	UserInputException
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
		$statement->execute([
			current($this->objectIDs),
			WCF::getUser()->userID
		]);
		$row = $statement->fetchArray();
		
		WCF::getTPL()->assign('hideConversation', $row['hideConversation']);
		
		return [
			'actionName' => 'getLeaveForm',
			'template' => WCF::getTPL()->fetch('conversationLeave')
		];
	}
	
	/**
	 * Validates parameters to hide conversations.
	 * 
	 * @throws	PermissionDeniedException
	 * @throws	UserInputException
	 */
	public function validateHideConversation() {
		$this->parameters['hideConversation'] = isset($this->parameters['hideConversation']) ? intval($this->parameters['hideConversation']) : null;
		if ($this->parameters['hideConversation'] === null || !in_array($this->parameters['hideConversation'], [Conversation::STATE_DEFAULT, Conversation::STATE_HIDDEN, Conversation::STATE_LEFT])) {
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
	 * @return	string[]
	 */
	public function hideConversation() {
		$sql = "UPDATE	wcf".WCF_N."_conversation_to_user
			SET	hideConversation = ?
			WHERE	conversationID = ?
				AND participantID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		
		WCF::getDB()->beginTransaction();
		foreach ($this->objectIDs as $conversationID) {
			$statement->execute([
				$this->parameters['hideConversation'],
				$conversationID,
				WCF::getUser()->userID
			]);
		}
		WCF::getDB()->commitTransaction();
		
		// reset user's conversation counters if user leaves conversation
		// permanently
		if ($this->parameters['hideConversation'] == Conversation::STATE_LEFT) {
			UserStorageHandler::getInstance()->reset([WCF::getUser()->userID], 'conversationCount');
			UserStorageHandler::getInstance()->reset([WCF::getUser()->userID], 'unreadConversationCount');
		}
		
		// add modification log entry
		if ($this->parameters['hideConversation'] == Conversation::STATE_LEFT) {
			if (empty($this->objects)) $this->readObjects();
			
			foreach ($this->getObjects() as $conversation) {
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
			$conditionBuilder->add('conversation.conversationID IN (?)', [$this->objectIDs]);
			$conditionBuilder->add('conversation_to_user.conversationID IS NULL');
			$sql = "SELECT		DISTINCT conversation.conversationID
				FROM		wcf".WCF_N."_conversation conversation
				LEFT JOIN	wcf".WCF_N."_conversation_to_user conversation_to_user
				ON		(	conversation_to_user.conversationID = conversation.conversationID
						AND	conversation_to_user.hideConversation <> ".Conversation::STATE_LEFT."
						AND	conversation_to_user.participantID IS NOT NULL)
				".$conditionBuilder;
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute($conditionBuilder->getParameters());
			$conversationIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);
			
			if (!empty($conversationIDs)) {
				$action = new ConversationAction($conversationIDs, 'delete');
				$action->executeAction();
			}
		}
		
		return [
			'actionName' => 'hideConversation',
			'redirectURL' => LinkHandler::getInstance()->getLink('ConversationList')
		];
	}
	
	/**
	 * Validates parameters to return the mixed conversation list.
	 */
	public function validateGetMixedConversationList() {
		// does nothing
	}
	
	/**
	 * Returns a mixed conversation list with up to 10 unread conversations.
	 * 
	 * @return	mixed[][]
	 */
	public function getMixedConversationList() {
		$sqlSelect = '  , (SELECT participantID FROM wcf'.WCF_N.'_conversation_to_user WHERE conversationID = conversation.conversationID AND participantID <> conversation.userID AND isInvisible = 0 ORDER BY username, participantID LIMIT 1) AS otherParticipantID
				, (SELECT username FROM wcf'.WCF_N.'_conversation_to_user WHERE conversationID = conversation.conversationID AND participantID <> conversation.userID AND isInvisible = 0 ORDER BY username, participantID LIMIT 1) AS otherParticipant';
		
		$unreadConversationList = new UserConversationList(WCF::getUser()->userID);
		$unreadConversationList->sqlSelects .= $sqlSelect;
		$unreadConversationList->getConditionBuilder()->add('conversation_to_user.lastVisitTime < lastPostTime');
		$unreadConversationList->sqlLimit = 10;
		$unreadConversationList->sqlOrderBy = 'lastPostTime DESC';
		$unreadConversationList->readObjects();
		
		$conversations = [];
		$count = 0;
		foreach ($unreadConversationList as $conversation) {
			$conversations[] = $conversation;
			$count++;
		}
		
		if ($count < 10) {
			$conversationList = new UserConversationList(WCF::getUser()->userID);
			$conversationList->sqlSelects .= $sqlSelect;
			$conversationList->getConditionBuilder()->add('conversation_to_user.lastVisitTime >= lastPostTime');
			$conversationList->sqlLimit = (10 - $count);
			$conversationList->sqlOrderBy = 'lastPostTime DESC';
			$conversationList->readObjects();
			
			foreach ($conversationList as $conversation) {
				$conversations[] = $conversation;
			}
		}
		
		WCF::getTPL()->assign([
			'conversations' => $conversations
		]);
		
		$totalCount = ConversationHandler::getInstance()->getUnreadConversationCount();
		if ($count < 10 && $count < $totalCount) {
			UserStorageHandler::getInstance()->reset([WCF::getUser()->userID], 'unreadConversationCount');
		}
		
		return [
			'template' => WCF::getTPL()->fetch('conversationListUserPanel'),
			'totalCount' => $totalCount
		];
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
	 * 
	 * @throws	PermissionDeniedException
	 */
	public function validateGetAddParticipantsForm() {
		$this->conversation = $this->getSingleObject();
		if (!Conversation::isParticipant([$this->conversation->conversationID]) || !$this->conversation->canAddParticipants()) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * Shows the 'add participants' form.
	 * 
	 * @return	array
	 */
	public function getAddParticipantsForm() {
		return [
			'excludedSearchValues' => $this->conversation->getParticipantNames(),
			'maxItems' => WCF::getSession()->getPermission('user.conversation.maxParticipants') - $this->conversation->participants,
			'template' => WCF::getTPL()->fetch('conversationAddParticipants', 'wcf', ['conversation' => $this->conversation])
		];
	}
	
	/**
	 * Validates parameters to add new participants.
	 */
	public function validateAddParticipants() {
		$this->validateGetAddParticipantsForm();
		
		// validate participants
		$this->readStringArray('participants');
		
		if (!$this->conversation->getDecoratedObject()->isDraft) {
			$this->readString('visibility');
			if (!in_array($this->parameters['visibility'], ['all', 'new'])) {
				throw new UserInputException('visibility');
			}
			
			if ($this->parameters['visibility'] === 'all' && !$this->conversation->canAddParticipantsUnrestricted()) {
				throw new UserInputException('visibility');
			}
		}
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
				$errorMessage .= WCF::getLanguage()->getDynamicVariable('wcf.conversation.participants.error.'.$type['type'], ['errorData' => ['username' => $type['username']]]);
			}
			
			return [
				'actionName' => 'addParticipants',
				'errorMessage' => $errorMessage
			];
		}
		
		// validate limit
		$newCount = $this->conversation->participants + count($participantIDs);
		if ($newCount > WCF::getSession()->getPermission('user.conversation.maxParticipants')) {
			return [
				'actionName' => 'addParticipants',
				'errorMessage' => WCF::getLanguage()->getDynamicVariable('wcf.conversation.participants.error.tooManyParticipants')
			];
		}
		
		$count = 0;
		$successMessage = '';
		if (!empty($participantIDs)) {
			// check for already added participants
			if ($this->conversation->isDraft) {
				$draftData = unserialize($this->conversation->draftData);
				$draftData['participants'] = array_merge($draftData['participants'], $participantIDs);
				$data = ['data' => ['draftData' => serialize($draftData)]];
			}
			else {
				$data = [
					'participants' => $participantIDs,
					'visibility' => (isset($this->parameters['visibility'])) ? $this->parameters['visibility'] : 'all'
				];
			}
			
			$conversationAction = new ConversationAction([$this->conversation], 'update', $data);
			$conversationAction->executeAction();
			
			$count = count($participantIDs);
			$successMessage = WCF::getLanguage()->getDynamicVariable('wcf.conversation.edit.addParticipants.success', ['count' => $count]);
			
			ConversationModificationLogHandler::getInstance()->addParticipants($this->conversation->getDecoratedObject(), $participantIDs);
			
			if (!$this->conversation->isDraft) {
				// update participant summary
				$this->conversation->updateParticipantSummary();
			}
		}
		
		return [
			'count' => $count,
			'successMessage' => $successMessage
		];
	}
	
	/**
	 * Validates parameters to remove a participant from a conversation.
	 * 
	 * @throws	PermissionDeniedException
	 * @throws	UserInputException
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
		if ($this->parameters['userID'] == WCF::getUser()->userID || !Conversation::isParticipant([$this->conversation->conversationID]) || !Conversation::isParticipant([$this->conversation->conversationID], $this->parameters['userID'])) {
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
		UserStorageHandler::getInstance()->reset([$this->parameters['userID']], 'unreadConversationCount');
		
		return [
			'userID' => $this->parameters['userID']
		];
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
		$conditionBuilder->add('conversation_message.conversationID IN (?)', [$this->objectIDs]);
		$sql = "SELECT		conversationID, COUNT(messageID) AS messages, SUM(attachments) AS attachments
			FROM		wcf".WCF_N."_conversation_message conversation_message
			".$conditionBuilder."
			GROUP BY	conversationID";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditionBuilder->getParameters());
		
		$objectIDs = [];
		while ($row = $statement->fetchArray()) {
			if (!$row['messages']) {
				continue;
			}
			$objectIDs[] = $row['conversationID'];
			
			$conversationEditor = new ConversationEditor(new Conversation(null, [
				'conversationID' => $row['conversationID']
			]));
			$conversationEditor->update([
				'attachments' => $row['attachments'],
				'replies' => $row['messages'] - 1
			]);
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
	 * Validates the parameters to edit a conversation's subject.
	 * 
	 * @throws      PermissionDeniedException
	 */
	public function validateEditSubject() {
		$this->readString('subject');
		
		$this->conversation = $this->getSingleObject();
		if ($this->conversation->userID != WCF::getUser()->userID) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * Edits a conversation's subject.
	 * 
	 * @return      string[]
	 */
	public function editSubject() {
		$this->conversation->update([
			'subject' => mb_substr($this->parameters['subject'], 0, 255)
		]);
		
		return [
			'subject' => $this->parameters['subject']
		];
	}
	
	/**
	 * Adds conversation modification data.
	 * 
	 * @param	Conversation	$conversation
	 * @param	string		$key
	 * @param	mixed		$value
	 */
	protected function addConversationData(Conversation $conversation, $key, $value) {
		if (!isset($this->conversationData[$conversation->conversationID])) {
			$this->conversationData[$conversation->conversationID] = [];
		}
		
		$this->conversationData[$conversation->conversationID][$key] = $value;
	}
	
	/**
	 * Returns conversation data.
	 * 
	 * @return	mixed[][]
	 */
	protected function getConversationData() {
		return [
			'conversationData' => $this->conversationData
		];
	}
	
	/**
	 * Unmarks conversations.
	 * 
	 * @param	integer[]		$conversationIDs
	 */
	protected function unmarkItems(array $conversationIDs = []) {
		if (empty($conversationIDs)) {
			$conversationIDs = $this->objectIDs;
		}
		
		ClipboardHandler::getInstance()->unmark($conversationIDs, ClipboardHandler::getInstance()->getObjectTypeID('com.woltlab.wcf.conversation.conversation'));
	}
}
