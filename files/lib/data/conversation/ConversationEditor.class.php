<?php
namespace wcf\data\conversation;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\DatabaseObjectEditor;
use wcf\system\WCF;

/**
 * Extends the conversation object with functions to create, update and delete conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation
 * @category	Community Framework
 */
class ConversationEditor extends DatabaseObjectEditor {
	/**
	 * @see	wcf\data\DatabaseObjectEditor::$baseClass
	 */
	protected static $baseClass = 'wcf\data\conversation\Conversation';
	
	/**
	 * Adds a new message to this conversation.
	 * 
	 * @param	wcf\data\conversation\message\ConversationMessage	$message
	 */
	public function addMessage(ConversationMessage $message) {
		$data = array(
			'lastPoster' => $message->username,
			'lastPostTime' => $message->time,
			'lastPosterID' => $message->userID,
			'replies' => $this->replies + 1,
			'attachments' => $this->attachments + $message->attachments
		);
		
		$this->update($data);
	}
	
	/**
	 * Resets the participants of this conversation.
	 */
	public function resetParticipants() {
		$sql = "DELETE FROM	wcf".WCF_N."_conversation_to_user
			WHERE		conversationID = ?
					AND participantID <> ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array($this->conversationID, $this->userID));
	}
	
	/**
	 * Updates the participants of this conversation.
	 * 
	 * @param	array<integer>	$participantIDs
	 * @param	array<integer>	$invisibleParticipantIDs
	 */
	public function updateParticipants(array $participantIDs, array $invisibleParticipantIDs = array()) {
		if (!empty($participantIDs)) {
			WCF::getDB()->beginTransaction();
			$sql = "INSERT INTO		wcf".WCF_N."_conversation_to_user
							(conversationID, participantID, isInvisible)
				VALUES			(?, ?, ?)
				ON DUPLICATE KEY
				UPDATE			hideConversation = 0";
			$statement = WCF::getDB()->prepareStatement($sql);
			
			foreach ($participantIDs as $userID) {
				$statement->execute(array($this->conversationID, $userID, 0));
			}
			WCF::getDB()->commitTransaction();
		}
		
		if (!empty($invisibleParticipantIDs)) {
			WCF::getDB()->beginTransaction();
			$sql = "INSERT INTO		wcf".WCF_N."_conversation_to_user
							(conversationID, participantID, isInvisible)
				VALUES			(?, ?, ?)";
			$statement = WCF::getDB()->prepareStatement($sql);
			
			foreach ($invisibleParticipantIDs as $userID) {
				$statement->execute(array($this->conversationID, $userID, 1));
			}
			WCF::getDB()->commitTransaction();
		}
		
		$this->updateParticipantCount();
	}
	
	/**
	 * Updates participant count.
	 */
	public function updateParticipantCount() {
		$sql = "UPDATE	wcf".WCF_N."_conversation
			SET	participants = (
					SELECT	COUNT(*) AS count
					FROM	wcf".WCF_N."_conversation_to_user conversation_to_user
					WHERE	conversation_to_user.conversationID = conversationID
						AND hideConversation <> ?
						AND participantID <> ?
				)
			WHERE	conversationID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(
			Conversation::STATE_LEFT,
			$this->userID,
			$this->conversationID
		));
	}
	
	/**
	 * Updates the participant summary of this conversation.
	 */
	public function updateParticipantSummary() {
		$users = array();
		$sql = "SELECT		conversation_to_user.participantID AS userID, conversation_to_user.hideConversation, user_table.username
			FROM		wcf".WCF_N."_conversation_to_user conversation_to_user
			LEFT JOIN	wcf".WCF_N."_user user_table
			ON		(user_table.userID = conversation_to_user.participantID)
			WHERE		conversation_to_user.conversationID = ?
					AND conversation_to_user.participantID <> ?
					AND conversation_to_user.isInvisible = 0
			ORDER BY	user_table.username";
		$statement = WCF::getDB()->prepareStatement($sql, 5);
		$statement->execute(array($this->conversationID, $this->userID));
		while ($row = $statement->fetchArray()) {
			$users[] = $row;
		}
		
		$this->update(array(
			'participantSummary' => serialize($users)
		));
	}
	
	/**
	 * Removes a participant from this conversation.
	 * 
	 * @param	integer		$userID
	 */
	public function removeParticipant($userID) {
		$sql = "UPDATE	wcf".WCF_N."_conversation_to_user
			SET	hideConversation = ?
			WHERE	conversationID = ?
				AND participantID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(
			2,
			$this->conversationID,
			$userID
		));
		
		// decrease participant count unless it is the author
		if ($userID != $this->userID) {
			$this->updateCounters(array(
				'participants' => -1
			));
		}
	}
	
	/**
	 * Updates the participant summary of the given conversations.
	 * 
	 * @param	array<integer>		$conversationIDs
	 */
	public static function updateParticipantSummaries(array $conversationIDs) {
		$conversationList = new ConversationList();
		$conversationList->getConditionBuilder()->add('conversation.conversationID IN (?)', array($conversationIDs));
		$conversationList->readObjects();
		
		foreach ($conversationList as $conversation) {
			$editor = new ConversationEditor($conversation);
			$editor->updateParticipantSummary();
		}
	}
}
