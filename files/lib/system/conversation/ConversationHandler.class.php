<?php
namespace wcf\system\conversation;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\package\PackageDependencyHandler;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\SingletonFactory;
use wcf\system\WCF;

class ConversationHandler extends SingletonFactory {
	/**
	 * number of unread conversations
	 * @var integer
	 */
	protected $unreadConversationCount = null;
	
	/**
	 * number of conversations
	 * @var integer
	 */
	protected $conversationCount = null;
	
	/**
	 * Returns the number of unread conversations for the active user.
	 * 
	 * @return	integer
	 */
	public function getUnreadConversationCount() {
		if ($this->unreadConversationCount === null) {
			$this->unreadConversationCount = 0;
		
			if (WCF::getUser()->userID) {
				// load storage data
				UserStorageHandler::getInstance()->loadStorage(array(WCF::getUser()->userID));
					
				// get ids
				$data = UserStorageHandler::getInstance()->getStorage(array(WCF::getUser()->userID), 'unreadConversationCount');
				
				// cache does not exist or is outdated
				if ($data[WCF::getUser()->userID] === null) {
					$conditionBuilder = new PreparedStatementConditionBuilder();
					$conditionBuilder->add('conversation.conversationID = conversation_to_user.conversationID');
					$conditionBuilder->add('conversation_to_user.participantID = ?', array(WCF::getUser()->userID));
					$conditionBuilder->add('conversation_to_user.hideConversation = 0');
					$conditionBuilder->add('conversation_to_user.lastVisitTime < conversation.lastPostTime');
					
					$sql = "SELECT	COUNT(*) AS count
						FROM	wcf".WCF_N."_conversation_to_user conversation_to_user,
							wcf".WCF_N."_conversation conversation
						".$conditionBuilder->__toString();
					$statement = WCF::getDB()->prepareStatement($sql);
					$statement->execute($conditionBuilder->getParameters());
					$row = $statement->fetchArray();
					$this->unreadConversationCount = $row['count'];
					
					// update storage data
					UserStorageHandler::getInstance()->update(WCF::getUser()->userID, 'unreadConversationCount', serialize($this->unreadConversationCount), PackageDependencyHandler::getInstance()->getPackageID('com.woltlab.wcf.conversation'));
				}
				else {
					$this->unreadConversationCount = unserialize($data[WCF::getUser()->userID]);
				}
			}
		}
		
		return $this->unreadConversationCount;
	}
	
	/**
	 * Returns the number of conversations for the active user.
	 * 
	 * @return	integer
	 */
	public function getConversationCount() {
		if ($this->conversationCount === null) {
			$this->conversationCount = 0;
		
			if (WCF::getUser()->userID) {
				// load storage data
				UserStorageHandler::getInstance()->loadStorage(array(WCF::getUser()->userID));
					
				// get ids
				$data = UserStorageHandler::getInstance()->getStorage(array(WCF::getUser()->userID), 'conversationCount');
				
				// cache does not exist or is outdated
				if ($data[WCF::getUser()->userID] === null) {
					$conditionBuilder1 = new PreparedStatementConditionBuilder();
					$conditionBuilder1->add('conversation_to_user.participantID = ?', array(WCF::getUser()->userID));
					$conditionBuilder1->add('conversation_to_user.hideConversation IN (0,1)');
					$conditionBuilder2 = new PreparedStatementConditionBuilder();
					$conditionBuilder2->add('conversation.userID = ?', array(WCF::getUser()->userID));
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
					$this->conversationCount = $row['count'];
					
					// update storage data
					UserStorageHandler::getInstance()->update(WCF::getUser()->userID, 'conversationCount', serialize($this->conversationCount), PackageDependencyHandler::getInstance()->getPackageID('com.woltlab.wcf.conversation'));
				}
				else {
					$this->conversationCount = unserialize($data[WCF::getUser()->userID]);
				}
			}
		}
		
		return $this->conversationCount;
	}
}
