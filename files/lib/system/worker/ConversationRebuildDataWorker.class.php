<?php
namespace wcf\system\worker;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationEditor;
use wcf\system\WCF;

/**
 * Worker implementation for updating conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2013 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf
 * @subpackage	system.worker
 * @category	Community Framework
 */
class ConversationRebuildDataWorker extends AbstractRebuildDataWorker {
	/**
	 * @see	wcf\system\worker\AbstractRebuildDataWorker::$objectListClassName
	 */
	protected $objectListClassName = 'wcf\data\conversation\ConversationList';
	
	/**
	 * @see	wcf\system\worker\AbstractWorker::$limit
	 */
	protected $limit = 100;
	
	/**
	 * @see	wcf\system\worker\AbstractRebuildDataWorker::initObjectList
	 */
	protected function initObjectList() {
		parent::initObjectList();
		
		$this->objectList->sqlOrderBy = 'conversation.conversationID';
	}
	
	/**
	 * @see	wcf\system\worker\IWorker::execute()
	 */
	public function execute() {
		parent::execute();
		
		// prepare statements
		$sql = "SELECT		messageID, time, userID, username
			FROM		wcf".WCF_N."_conversation_message
			WHERE		conversationID = ?
			ORDER BY	time";
		$firstMessageStatement = WCF::getDB()->prepareStatement($sql, 1);
		$sql = "SELECT		time, userID, username
			FROM		wcf".WCF_N."_conversation_message
			WHERE		conversationID = ?
			ORDER BY	time DESC";
		$lastMessageStatement = WCF::getDB()->prepareStatement($sql, 1);
		$sql = "SELECT	COUNT(*) AS messages,
				SUM(attachments) AS attachments
			FROM	wcf".WCF_N."_conversation_message
			WHERE	conversationID = ?";
		$statsStatement = WCF::getDB()->prepareStatement($sql);
		$sql = "SELECT	COUNT(*) AS participants
			FROM	wcf".WCF_N."_conversation_to_user conversation_to_user
			WHERE	conversation_to_user.conversationID = ?
				AND conversation_to_user.hideConversation <> ?
				AND conversation_to_user.participantID <> ?
				AND conversation_to_user.isInvisible = ?";
		$participantCounterStatement = WCF::getDB()->prepareStatement($sql);
		$sql = "SELECT		conversation_to_user.participantID AS userID, conversation_to_user.hideConversation, user_table.username
			FROM		wcf".WCF_N."_conversation_to_user conversation_to_user
			LEFT JOIN	wcf".WCF_N."_user user_table
			ON		(user_table.userID = conversation_to_user.participantID)
			WHERE		conversation_to_user.conversationID = ?
					AND conversation_to_user.participantID <> ?
					AND conversation_to_user.isInvisible = ?
			ORDER BY	user_table.username";
		$participantStatement = WCF::getDB()->prepareStatement($sql);
		
		foreach ($this->objectList as $conversation) {
			$editor = new ConversationEditor($conversation);
			$data = array();
			
			// get first post
			$firstMessageStatement->execute(array($conversation->conversationID));
			if (($row = $firstMessageStatement->fetchArray()) !== false) {
				$data['firstMessageID'] = $row['messageID'];
				$data['time'] = $row['time'];
				$data['userID'] = $row['userID'];
				$data['username'] = $row['username'];
			}
			
			// get last post
			$lastMessageStatement->execute(array($conversation->conversationID));
			if (($row = $lastMessageStatement->fetchArray()) !== false) {
				$data['lastPostTime'] = $row['time'];
				$data['lastPosterID'] = $row['userID'];
				$data['lastPoster'] = $row['username'];
			}
			
			// get stats
			$statsStatement->execute(array($conversation->conversationID));
			$row = $statsStatement->fetchArray();
			$data['replies'] = ($row['messages'] ? $row['messages'] - 1 : 0);
			$data['attachments'] = ($row['attachments'] ?: 0);
			
			// get number of participants
			$participantCounterStatement->execute(array($conversation->conversationID, Conversation::STATE_LEFT, $conversation->userID, 0));
			$row = $participantCounterStatement->fetchArray();
			$data['participants'] = $row['participants'];
			
			// get participant summary
			$participantStatement->execute(array($conversation->conversationID, $conversation->userID, 0));
			$users = array();
			while ($row = $participantStatement->fetchArray()) {
				$users[] = $row;
			}
			$data['participantSummary'] = serialize($users);
			
			$editor->update($data);
		}
	}
}
