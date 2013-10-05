<?php
namespace wcf\system\search;
use wcf\data\conversation\message\SearchResultConversationMessageList;
use wcf\form\IForm;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * An implementation of ISearchableObjectType for searching in conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.search
 * @category	Community Framework
 */
class ConversationMessageSearch extends AbstractSearchableObjectType {
	/**
	 * message data cache
	 * @var	array<wcf\data\conversation\message\SearchResultConversationMessage>
	 */
	public $messageCache = array();
	
	/**
	 * @see	wcf\system\search\ISearchableObjectType::cacheObjects()
	 */
	public function cacheObjects(array $objectIDs, array $additionalData = null) {
		$messageList = new SearchResultConversationMessageList();
		$messageList->getConditionBuilder()->add('conversation_message.messageID IN (?)', array($objectIDs));
		$messageList->readObjects();
		foreach ($messageList->getObjects() as $message) {
			$this->messageCache[$message->messageID] = $message;
		}
	}
	
	/**
	 * @see	wcf\system\search\ISearchableObjectType::getObject()
	 */
	public function getObject($objectID) {
		if (isset($this->messageCache[$objectID])) return $this->messageCache[$objectID];
		return null;
	}
	
	/**
	 * @see	wcf\system\search\ISearchableObjectType::getJoins()
	 */
	public function getJoins() {
		return "JOIN wcf".WCF_N."_conversation_to_user conversation_to_user ON (conversation_to_user.participantID = ".WCF::getUser()->userID." AND conversation_to_user.conversationID = ".$this->getTableName().".conversationID)
		LEFT JOIN wcf".WCF_N."_conversation conversation ON (conversation.conversationID = ".$this->getTableName().".conversationID)";
	}
	
	/**
	 * @see	wcf\system\search\ISearchableObjectType::getTableName()
	 */
	public function getTableName() {
		return 'wcf'.WCF_N.'_conversation_message';
	}
	
	/**
	 * @see	wcf\system\search\ISearchableObjectType::getIDFieldName()
	 */
	public function getIDFieldName() {
		return $this->getTableName().'.messageID';
	}
	
	/**
	 * @see	wcf\system\search\ISearchableObjectType::getSubjectFieldName()
	 */
	public function getSubjectFieldName() {
		return 'conversation.subject';
	}
	
	/**
	 * @see	wcf\system\search\ISearchableObjectType::getConditions()
	 */
	public function getConditions(IForm $form = null) {
		$conditionBuilder = new PreparedStatementConditionBuilder();
		$conditionBuilder->add('conversation_to_user.hideConversation IN (0,1)');
		
		return $conditionBuilder;
	}
}
