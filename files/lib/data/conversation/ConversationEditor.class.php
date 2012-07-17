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
 * @category 	Community Framework
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
		$sql = "DELETE FROM	wcf".WCF_N."_ocnversation_to_user
			WHERE		conversationID = ?
					AND participantID <> ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array($this->conversationID, $this->userID));
	}
	
	/**
	 * Updates the participants of this conversation.
	 * 
	 * @param	array<integer>	$participants
	 * @param	array<integer>	$invisibleParticipants
	 */
	public function updateParticipants(array $participants, array $invisibleParticipants = array()) {
		$sql = "INSERT INTO	wcf".WCF_N."_conversation_to_user
					(conversationID, participantID, isInvisible)
			VALUES		(?, ?, ?)";
		$statement = WCF::getDB()->prepareStatement($sql);
		if (!empty($participants)) {
			foreach ($participants as $userID) {
				$statement->execute(array($this->conversationID, $userID, 0));
			}
		}
		if (!empty($this->parameters['invisibleParticipants'])) {
			foreach ($this->parameters['invisibleParticipants'] as $userID) {
				$statement->execute(array($conversation->conversationID, $userID, 1));
			}
		}
	}
	
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
}
