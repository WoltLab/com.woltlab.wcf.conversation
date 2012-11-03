<?php
namespace wcf\system\moderation\queue\report;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationList;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\message\ConversationMessageAction;
use wcf\data\conversation\message\ConversationMessageList;
use wcf\data\conversation\message\ViewableConversationMessage;
use wcf\data\moderation\queue\ModerationQueue;
use wcf\data\moderation\queue\ViewableModerationQueue;
use wcf\system\exception\SystemException;
use wcf\system\moderation\queue\ModerationQueueManager;
use wcf\system\WCF;

/**
 * An implementation of IModerationQueueReportHandler for conversation messages.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.moderation.queue
 * @category	Community Framework
 */
class ConversationMessageModerationQueueReportHandler implements IModerationQueueReportHandler {
	/**
	 * list of conversation message
	 * @var	array<wcf\data\conversation\message\ConversationMessage>
	 */
	protected static $messages = array();
	
	/**
	 * @see	wcf\system\moderation\queue\IModerationQueueHandler::assignQueues()
	 */
	public function assignQueues(array $queues) {
		$assignments = array();
		foreach ($queues as $queue) {
			$assignUser = false;
			if (WCF::getSession()->getPermission('mod.conversation.canModerateConversation')) {
				$assignUser = true;
			}
				
			$assignments[$queue->queueID] = $assignUser;
		}
	
		ModerationQueueManager::getInstance()->setAssignment($assignments);
	}
	
	/**
	 * @see	wcf\system\moderation\queue\report\IModerationQueueReportHandler::canReport()
	 */
	public function canReport($objectID) {
		if (!$this->isValid($objectID)) {
			return false;
		}
		
		if (!Conversation::isParticipant(array($this->getMessage($objectID)->conversationID))) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * @see	wcf\system\moderation\queue\IModerationQueueHandler::getContainerID()
	 */
	public function getContainerID($objectID) {
		return 0;
	}
	
	/**
	 * @see	wcf\system\moderation\queue\report\IModerationQueueReportHandler::getReportedContent()
	 */
	public function getReportedContent(ViewableModerationQueue $queue) {
		WCF::getTPL()->assign(array(
			'message' => new ViewableConversationMessage($queue->getAffectedObject())
		));
	
		return WCF::getTPL()->fetch('moderationConversationMessage');
	}
	
	/**
	 * @see	wcf\system\moderation\queue\report\IModerationQueueReportHandler::getReportedObject()
	 */
	public function getReportedObject($objectID) {
		if ($this->isValid($objectID)) {
			return $this->getMessage($objectID);
		}
	
		return null;
	}
	
	/**
	 * @see	wcf\system\moderation\queue\IModerationQueueHandler::isValid()
	 */
	public function isValid($objectID) {
		if ($this->getMessage($objectID) === null) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Returns a conversation message object by message id or null if message id is invalid.
	 * 
	 * @param	integer		$objectID
	 * @return	wcf\data\conversation\message\ConversationMessage
	 */
	protected function getMessage($objectID) {
		if (!array_key_exists($objectID, self::$messages)) {
			self::$messages[$objectID] = new ConversationMessage($objectID);
			if (!self::$messages[$objectID]->messageID) {
				self::$messages[$objectID] = null;
			}
		}
		
		return self::$messages[$objectID];
	}
	
	/**
	 * @see	wcf\system\moderation\queue\IModerationQueueHandler::populate()
	 */
	public function populate(array $queues) {
		$objectIDs = array();
		foreach ($queues as $object) {
			$objectIDs[] = $object->objectID;
		}
		
		// fetch messages
		$messageList = new ConversationMessageList();
		$messageList->getConditionBuilder()->add("conversation_message.messageID IN (?)", array($objectIDs));
		$messageList->sqlLimit = 0;
		$messageList->readObjects();
		$messages = $messageList->getObjects();
		
		// fetch conversations
		$conversationIDs = array();
		foreach ($messages as $message) {
			$conversationIDs[] = $message->conversationID;
		}
		
		$conversationList = new ConversationList();
		$conversationList->getConditionBuilder()->add("conversation.conversationID IN (?)", array($conversationIDs));
		$conversationList->sqlLimit = 0;
		$conversationList->readObjects();
		$conversations = $conversationList->getObjects();
		
		foreach ($queues as $object) {
			if (isset($messages[$object->objectID])) {
				$message = $messages[$object->objectID];
				$message->setConversation($conversations[$message->conversationID]);
				
				$object->setAffectedObject($message);
			}
		}
	}
	
	/**
	 * @see	wcf\system\moderation\queue\IModerationQueueHandler::removeContent()
	 */
	public function removeContent(ModerationQueue $queue, $message) {
		if ($this->isValid($queue->objectID)) {
			$messageAction = new ConversationMessageAction(array($this->getMessage($queue->objectID)), 'delete');
			$messageAction->executeAction();
		}
	}
}
