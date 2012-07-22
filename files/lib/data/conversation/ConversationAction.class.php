<?php
namespace wcf\data\conversation;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\message\ConversationMessageAction;
use wcf\data\conversation\message\ViewableConversationMessageList;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\ValidateActionException;
use wcf\system\package\PackageDependencyHandler;
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
 * @category 	Community Framework
 */
class ConversationAction extends AbstractDatabaseObjectAction {
	/**
	 * @see wcf\data\AbstractDatabaseObjectAction::$className
	 */
	protected $className = 'wcf\data\conversation\ConversationEditor';
	
	/**
	 * list of conversation data modifications
	 * @var	array<array>
	 */
	protected $conversationData = array();
	
	/**
	 * @see wcf\data\AbstractDatabaseObjectAction::create()
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
		
		if (!count($this->objects)) {
			$this->readObjects();
		}
		
		$sql = "UPDATE	wcf".WCF_N."_conversation_to_user
			SET	lastVisitTime = ?
			WHERE	participantID = ?
				AND conversationID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		foreach ($this->objects as $conversation) {
			$statement->execute(array($this->parameters['visitTime'], WCF::getUser()->userID, $conversation->conversationID));
		}
		
		// reset storage
		UserStorageHandler::getInstance()->reset(array(WCF::getUser()->userID), 'unreadConversationCount', PackageDependencyHandler::getInstance()->getPackageID('com.woltlab.wcf.conversation'));
	}
	
	/**
	 * Validates the mark as read action.
	 */
	public function validateMarkAsRead() {
		// @todo: implement me
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
		if (!count($this->objects)) {
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
			$this->addConversationData($conversation, 'isClosed', 1);
		}
		
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
			$this->addConversationData($conversation, 'isClosed', 0);
		}
	
		return $this->getConversationData();
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
}
