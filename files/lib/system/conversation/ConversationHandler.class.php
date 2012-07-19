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
}
