<?php
namespace wcf\data\conversation;
use wcf\system\WCF;

/**
 * Represents a list of conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation
 * @category 	Community Framework
 */
class UserConversationList extends ConversationList {
	public static $availableFilters = array('hidden', 'draft', 'outbox');
	public $filter = '';
	
	/**
	 * decorator class name
	 * @var string
	 */
	public $decoratorClassName = 'wcf\data\conversation\ViewableConversation';
	
	/**
	 * Creates a new UserConversationList
	 * 
	 * @param	integer		$userID
	 * @param	string		$filter
	 */
	public function __construct($userID, $filter = '') {
		parent::__construct();
		
		$this->filter = $filter;
		
		// apply filter
		if ($this->filter == 'draft') {
			$this->getConditionBuilder()->add('conversation.userID = ?', array($userID));
			$this->getConditionBuilder()->add('conversation.isDraft = 1');
		}
		else {
			$this->getConditionBuilder()->add('conversation_to_user.participantID = ?', array($userID));
			$this->getConditionBuilder()->add('conversation_to_user.hideConversation = ?', array(($this->filter == 'hidden' ? 1 : 0)));
			$this->sqlConditionJoins = "LEFT JOIN wcf".WCF_N."_conversation conversation ON (conversation.conversationID = conversation_to_user.conversationID)";
			if ($this->filter == 'outbox') $this->getConditionBuilder()->add('conversation.userID = ?', array($userID));
		}
		
		// own posts
		$this->sqlSelects = "DISTINCT conversation_message.userID AS ownPosts";
		$this->sqlJoins = "LEFT JOIN wcf".WCF_N."_conversation_message conversation_message ON (conversation_message.conversationID = conversation.conversationID AND conversation_message.userID = ".$userID.")";
		
		// user info
		if (!empty($this->sqlSelects)) $this->sqlSelects .= ',';
		$this->sqlSelects .= "conversation_to_user.*";
		$this->sqlJoins .= "LEFT JOIN wcf".WCF_N."_conversation_to_user conversation_to_user ON (conversation_to_user.participantID = ".$userID." AND conversation_to_user.conversationID = conversation.conversationID)";
		
		// get avatars
		if (!empty($this->sqlSelects)) $this->sqlSelects .= ',';
		$this->sqlSelects .= "user_avatar.*, user_table.email, user_table.disableAvatar";
		$this->sqlJoins .= " LEFT JOIN wcf".WCF_N."_user user_table ON (user_table.userID = conversation.userID)";
		$this->sqlJoins .= " LEFT JOIN wcf".WCF_N."_user_avatar user_avatar ON (user_avatar.avatarID = user_table.avatarID)";
		
		if (!empty($this->sqlSelects)) $this->sqlSelects .= ',';
		$this->sqlSelects .= "lastposter_avatar.avatarID AS lastPosterAvatarID, lastposter_avatar.avatarName AS lastPosterAvatarName, lastposter_avatar.avatarExtension AS lastPosterAvatarExtension, lastposter_avatar.width AS lastPosterAvatarWidth, lastposter_avatar.height AS lastPosterAvatarHeight,";
		$this->sqlSelects .= "lastposter.email AS lastPosterEmail, lastposter.disableAvatar AS lastPosterDisableAvatar";
		$this->sqlJoins .= " LEFT JOIN wcf".WCF_N."_user lastposter ON (lastposter.userID = conversation.lastPosterID)";
		$this->sqlJoins .= " LEFT JOIN wcf".WCF_N."_user_avatar lastposter_avatar ON (lastposter_avatar.avatarID = lastposter.avatarID)";
	}
	
	/**
	 * @see	wcf\data\DatabaseObjectList::countObjects()
	 */
	public function countObjects() {
		if ($this->filter == 'draft') return parent::countObjects();
		
		$sql = "SELECT	COUNT(*) AS count
			FROM	wcf".WCF_N."_conversation_to_user conversation_to_user
			".$this->sqlConditionJoins."
			".$this->getConditionBuilder()->__toString();
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($this->getConditionBuilder()->getParameters());
		$row = $statement->fetchArray();
		return $row['count'];
	}
	
	/**
	 * @see	wcf\data\DatabaseObjectList::readObjectIDs()
	 */
	public function readObjectIDs() {
		if ($this->filter == 'draft') return parent::readObjectIDs();
		
		$this->objectIDs = array();
		$sql = "SELECT	conversation_to_user.conversationID AS objectID
			FROM	wcf".WCF_N."_conversation_to_user conversation_to_user
				".$this->sqlConditionJoins."
				".$this->getConditionBuilder()->__toString()."
				".(!empty($this->sqlOrderBy) ? "ORDER BY ".$this->sqlOrderBy : '');
		$statement = WCF::getDB()->prepareStatement($sql, $this->sqlLimit, $this->sqlOffset);
		$statement->execute($this->getConditionBuilder()->getParameters());
		while ($row = $statement->fetchArray()) {
			$this->objectIDs[] = $row['objectID'];
		}
	}
	
	/**
	 * @see	wcf\data\DatabaseObjectList::readObjects()
	 */
	public function readObjects() {
		if ($this->objectIDs === null) $this->readObjectIDs();
		parent::readObjects();
		
		foreach ($this->objects as $conversationID => $conversation) {
			$this->objects[$conversationID] = new $this->decoratorClassName($conversation);
		}
	}
}