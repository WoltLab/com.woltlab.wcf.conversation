<?php
namespace wcf\system\conversation;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\SingletonFactory;
use wcf\system\WCF;

/**
 * Handles the number of conversations and unread conversations of the active user.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2015 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.conversation
 * @category	Community Framework
 */
class ConversationHandler extends SingletonFactory {
	/**
	 * number of unread conversations
	 * @var	array<integer>
	 */
	protected $unreadConversationCount = array();
	
	/**
	 * number of conversations
	 * @var	array<integer>
	 */
	protected $conversationCount = array();
	
	/**
	 * Returns the number of unread conversations for given user.
	 * 
	 * @param	integer		$userID
	 * @param	boolean		$skipCache
	 * @return	integer
	 */
	public function getUnreadConversationCount($userID = null, $skipCache = false) {
		if ($userID === null) $userID = WCF::getUser()->userID;
		
		if (!isset($this->unreadConversationCount[$userID]) || $skipCache) {
			$this->unreadConversationCount[$userID] = 0;
			
			// load storage data
			UserStorageHandler::getInstance()->loadStorage(array($userID));
			
			// get ids
			$data = UserStorageHandler::getInstance()->getStorage(array($userID), 'unreadConversationCount');
			
			// cache does not exist or is outdated
			if ($data[$userID] === null || $skipCache) {
				$conditionBuilder = new PreparedStatementConditionBuilder();
				$conditionBuilder->add('conversation.conversationID = conversation_to_user.conversationID');
				$conditionBuilder->add('conversation_to_user.participantID = ?', array($userID));
				$conditionBuilder->add('conversation_to_user.hideConversation = 0');
				$conditionBuilder->add('conversation_to_user.lastVisitTime < conversation.lastPostTime');
				
				$sql = "SELECT	COUNT(*) AS count
					FROM	wcf".WCF_N."_conversation_to_user conversation_to_user,
						wcf".WCF_N."_conversation conversation
					".$conditionBuilder->__toString();
				$statement = WCF::getDB()->prepareStatement($sql);
				$statement->execute($conditionBuilder->getParameters());
				$row = $statement->fetchArray();
				$this->unreadConversationCount[$userID] = $row['count'];
				
				// update storage data
				UserStorageHandler::getInstance()->update($userID, 'unreadConversationCount', serialize($this->unreadConversationCount[$userID]));
			}
			else {
				$this->unreadConversationCount[$userID] = unserialize($data[$userID]);
			}
		}
		
		return $this->unreadConversationCount[$userID];
	}
	
	/**
	 * Returns the number of conversations for given user.
	 * 
	 * @param	integer		$userID
	 * @return	integer
	 */
	public function getConversationCount($userID = null) {
		if ($userID === null) $userID = WCF::getUser()->userID;
		
		if (!isset($this->conversationCount[$userID])) {
			$this->conversationCount[$userID] = 0;
			
			// load storage data
			UserStorageHandler::getInstance()->loadStorage(array($userID));
			
			// get ids
			$data = UserStorageHandler::getInstance()->getStorage(array($userID), 'conversationCount');
			
			// cache does not exist or is outdated
			if ($data[$userID] === null) {
				$conditionBuilder1 = new PreparedStatementConditionBuilder();
				$conditionBuilder1->add('conversation_to_user.participantID = ?', array($userID));
				$conditionBuilder1->add('conversation_to_user.hideConversation IN (0,1)');
				$conditionBuilder2 = new PreparedStatementConditionBuilder();
				$conditionBuilder2->add('conversation.userID = ?', array($userID));
				$conditionBuilder2->add('conversation.isDraft = 1');
				
				$sql = "SELECT (SELECT	COUNT(*)
						FROM	wcf".WCF_N."_conversation_to_user conversation_to_user
						".$conditionBuilder1->__toString().")
						+
						(SELECT	COUNT(*)
						FROM	wcf".WCF_N."_conversation conversation
						".$conditionBuilder2->__toString().") AS count";
				$statement = WCF::getDB()->prepareStatement($sql);
				$statement->execute(array_merge($conditionBuilder1->getParameters(), $conditionBuilder2->getParameters()));
				$row = $statement->fetchArray();
				$this->conversationCount[$userID] = $row['count'];
				
				// update storage data
				UserStorageHandler::getInstance()->update($userID, 'conversationCount', serialize($this->conversationCount[$userID]));
			}
			else {
				$this->conversationCount[$userID] = unserialize($data[$userID]);
			}
		}
		
		return $this->conversationCount[$userID];
	}
}
