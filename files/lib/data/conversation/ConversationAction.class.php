<?php
namespace wcf\data\conversation;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\conversation\message\ConversationMessageAction;
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
		
		// save participants
		$sql = "INSERT INTO	wcf".WCF_N."_conversation_to_user
					(conversationID, participantID, isInvisible)
			VALUES		(?, ?, ?)";
		$statement = WCF::getDB()->prepareStatement($sql);
		if (!empty($this->parameters['participants'])) {
			foreach ($this->parameters['participants'] as $userID) {
				$statement->execute(array($conversation->conversationID, $userID, 0));
			}
		}
		if (!empty($this->parameters['invisibleParticipants'])) {
			foreach ($this->parameters['invisibleParticipants'] as $userID) {
				$statement->execute(array($conversation->conversationID, $userID, 1));
			}
		}
		// add author
		$statement->execute(array($conversation->conversationID, $data['userID'], 0));
		
		// update participant summary
		$conversationEditor = new ConversationEditor($conversation);
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
		
		return $conversation;
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
		if (WCF::getUser()->userID) {
			// todo UserStorageHandler::getInstance()->reset(array(WCF::getUser()->userID), 'unreadConversations', PackageDependencyHandler::getInstance()->getPackageID('com.woltlab.wcf.conversation'));
		}
	}
	
	/**
	 * Validates the mark as read action.
	 */
	public function validateMarkAsRead() {
		// todo
	}
}
