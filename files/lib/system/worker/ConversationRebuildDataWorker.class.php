<?php
namespace wcf\system\worker;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationAction;
use wcf\data\conversation\ConversationEditor;
use wcf\data\conversation\ConversationList;
use wcf\system\WCF;

/**
 * Worker implementation for updating conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\Worker
 * 
 * @method	ConversationList	getObjectList()
 */
class ConversationRebuildDataWorker extends AbstractRebuildDataWorker {
	/**
	 * @inheritDoc
	 */
	protected $limit = 100;
	
	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * @inheritDoc
	 */
	public function countObjects() {
		if ($this->count === null) {
			$this->count = 0;
			$sql = "SELECT	MAX(conversationID) AS conversationID
				FROM	wcf".WCF_N."_conversation";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute();
			$row = $statement->fetchArray();
			if ($row !== false) $this->count = $row['conversationID'];
		}
	}
	
	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * @inheritDoc
	 */
	protected function initObjectList() {
		$this->objectList = new ConversationList();
		$this->objectList->sqlOrderBy = 'conversation.conversationID';
	}
	
	/**
	 * @inheritDoc
	 */
	public function execute() {
		$this->objectList->getConditionBuilder()->add('conversation.conversationID BETWEEN ? AND ?', [$this->limit * $this->loopCount + 1, $this->limit * $this->loopCount + $this->limit]);
		
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
		$participantStatement = WCF::getDB()->prepareStatement($sql, 5);
		
		$sql = "SELECT	COUNT(*) AS participants
			FROM	wcf".WCF_N."_conversation_to_user
			WHERE	conversationID = ?
				AND participantID IS NOT NULL";
		$existingParticipantStatement = WCF::getDB()->prepareStatement($sql, 5);
		
		$obsoleteConversations = [];
		foreach ($this->objectList as $conversation) {
			$editor = new ConversationEditor($conversation);
			
			// check for obsolete conversations
			$obsolete = false;
			if ($conversation->isDraft) {
				if (!$conversation->userID) $obsolete = true;
			}
			else {
				$existingParticipantStatement->execute([$conversation->conversationID]);
				$row = $existingParticipantStatement->fetchSingleRow();
				if (!$row['participants']) $obsolete = true;
			}
			if ($obsolete) {
				$obsoleteConversations[] = $editor;
				continue;
			}
			
			// update data
			$data = [];
			
			// get first post
			$firstMessageStatement->execute([$conversation->conversationID]);
			if (($row = $firstMessageStatement->fetchSingleRow()) !== false) {
				$data['firstMessageID'] = $row['messageID'];
				$data['lastPostTime'] = $data['time'] = $row['time'];
				$data['userID'] = $row['userID'];
				$data['username'] = $row['username'];
			}
			
			// get last post
			$lastMessageStatement->execute([$conversation->conversationID]);
			if (($row = $lastMessageStatement->fetchSingleRow()) !== false) {
				$data['lastPostTime'] = $row['time'];
				$data['lastPosterID'] = $row['userID'];
				$data['lastPoster'] = $row['username'];
			}
			
			// get stats
			$statsStatement->execute([$conversation->conversationID]);
			$row = $statsStatement->fetchSingleRow();
			$data['replies'] = ($row['messages'] ? $row['messages'] - 1 : 0);
			$data['attachments'] = ($row['attachments'] ?: 0);
			
			// get number of participants
			$participantCounterStatement->execute([$conversation->conversationID, Conversation::STATE_LEFT, $conversation->userID, 0]);
			$row = $participantCounterStatement->fetchSingleRow();
			$data['participants'] = $row['participants'];
			
			// get participant summary
			$participantStatement->execute([$conversation->conversationID, $conversation->userID, 0]);
			$users = [];
			while ($row = $participantStatement->fetchArray()) {
				$users[] = $row;
			}
			$data['participantSummary'] = serialize($users);
			
			$editor->update($data);
		}
		
		// delete obsolete conversations
		if (!empty($obsoleteConversations)) {
			$action = new ConversationAction($obsoleteConversations, 'delete');
			$action->executeAction();
		}
	}
}
