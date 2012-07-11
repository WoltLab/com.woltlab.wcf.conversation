<?php
namespace wcf\data\conversation;
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
