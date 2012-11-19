<?php
namespace wcf\system\user\online\location;
use wcf\data\user\online\UserOnline;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * Implementation of IUserOnlineLocation for the conversation page location.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.user.online.location
 * @category	Community Framework
 */
class ConversationLocation implements IUserOnlineLocation {
	/**
	 * conversation ids
	 * @var	array<integer>
	 */
	protected $conversationIDs = array();
	
	/**
	 * list of conversations
	 * @var	array<wcf\data\conversation\Conversation>
	 */
	protected $conversations = null;
	
	/**
	 * @see	wcf\system\user\online\location\IUserOnlineLocation::cache()
	 */
	public function cache(UserOnline $user) {
		if ($user->objectID) $this->conversationIDs[] = $user->objectID;
	}
	
	/**
	 * @see	wcf\system\user\online\location\IUserOnlineLocation::get()
	 */
	public function get(UserOnline $user, $languageVariable = '') {
		if ($this->conversations === null) {
			$this->readConversations();
		}
		
		if (!isset($this->conversations[$user->objectID])) {
			return '';
		}
		
		return WCF::getLanguage()->getDynamicVariable($languageVariable, array('conversation' => $this->conversations[$user->objectID]));
	}
	
	/**
	 * Loads the conversations.
	 */
	protected function readConversations() {
		$this->conversations = array();
		
		if (!WCF::getUser()->userID) return;
		if (empty($this->conversationIDs)) return;
		$this->conversationIDs = array_unique($this->conversationIDs);
		
		$conditionBuilder = new PreparedStatementConditionBuilder();
		$conditionBuilder->add('conversation_to_user.participantID = ?', array(WCF::getUser()->userID));
		$conditionBuilder->add('conversation_to_user.conversationID IN (?)', array($this->conversationIDs));
		
		$sql = "SELECT		conversation.*
			FROM		wcf".WCF_N."_conversation_to_user conversation_to_user
			LEFT JOIN	wcf".WCF_N."_conversation conversation
			ON		(conversation.conversationID = conversation_to_user.conversationID)
			".$conditionBuilder;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditionBuilder->getParameters());
		while ($conversation = $statement->fetchObject('\wcf\data\conversation\Conversation')) {
			$this->conversations[$conversation->conversationID] = $conversation;
		}
	}
}