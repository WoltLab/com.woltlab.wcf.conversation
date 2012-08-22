<?php
namespace wcf\data\conversation;
use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\label\ConversationLabelList;
use wcf\system\database\util\PreparedStatementConditionBuilder;
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
	/**
	 * list of available filters
	 * @var	array<string>
	 */
	public static $availableFilters = array('hidden', 'draft', 'outbox');
	
	/**
	 * active filter
	 * @var	string
	 */
	public $filter = '';
	
	/**
	 * label list object
	 * @var	wcf\data\conversation\label\ConversationLabelList
	 */
	public $labelList = null;
	
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
	 * @param	integer		$labelID
	 */
	public function __construct($userID, $filter = '', $labelID = 0) {
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
		
		// filter by label id
		if ($labelID) {
			// TODO: This is damn slow on MySQL
			$this->getConditionBuilder()->add("conversation.conversationID IN (
				SELECT	conversationID
				FROM	wcf".WCF_N."_conversation_label_to_object
				WHERE	labelID = ?
			)", array($labelID));
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
		$this->sqlSelects .= "user_avatar.*, user_table.email, user_table.disableAvatar, user_table.enableGravatar";
		$this->sqlJoins .= " LEFT JOIN wcf".WCF_N."_user user_table ON (user_table.userID = conversation.userID)";
		$this->sqlJoins .= " LEFT JOIN wcf".WCF_N."_user_avatar user_avatar ON (user_avatar.avatarID = user_table.avatarID)";
		
		if (!empty($this->sqlSelects)) $this->sqlSelects .= ',';
		$this->sqlSelects .= "lastposter_avatar.avatarID AS lastPosterAvatarID, lastposter_avatar.avatarName AS lastPosterAvatarName, lastposter_avatar.avatarExtension AS lastPosterAvatarExtension, lastposter_avatar.width AS lastPosterAvatarWidth, lastposter_avatar.height AS lastPosterAvatarHeight,";
		$this->sqlSelects .= "lastposter.email AS lastPosterEmail, lastposter.disableAvatar AS lastPosterDisableAvatar";
		$this->sqlJoins .= " LEFT JOIN wcf".WCF_N."_user lastposter ON (lastposter.userID = conversation.lastPosterID)";
		$this->sqlJoins .= " LEFT JOIN wcf".WCF_N."_user_avatar lastposter_avatar ON (lastposter_avatar.avatarID = lastposter.avatarID)";
	}
	
	/**
	 * @param	wcf\data\conversation\label\ConversationLabelList	$labelList
	 */
	public function setLabelList(ConversationLabelList $labelList) {
		$this->labelList = $labelList;
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
		
		$labels = $this->loadLabelAssignments();
		
		foreach ($this->objects as $conversationID => &$conversation) {
			$conversation = new $this->decoratorClassName($conversation);
			
			if (isset($labels[$conversationID])) {
				foreach ($labels[$conversationID] as $label) {
					$conversation->assignLabel($label);
				}
			}
		}
		unset($conversation);
	}
	
	/**
	 * Returns a list of conversation labels.
	 * 
	 * @return	array<wcf\data\conversation\label\ConversationLabel>
	 */
	protected function getLabels() {
		if ($this->labelList === null) {
			$this->labelList = ConversationLabel::getLabelsByUser();
		}
		
		return $this->labelList->getObjects();
	}
	
	/**
	 * Returns label assignments per conversation.
	 * 
	 * @return	array<array>
	 */
	protected function loadLabelAssignments() {
		$labels = $this->getLabels();
		if (empty($labels)) {
			return array();
		}
		
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("conversationID IN (?)", array(array_keys($this->objects)));
		$conditions->add("labelID IN (?)", array(array_keys($labels)));
		
		$sql = "SELECT	labelID, conversationID
			FROM	wcf".WCF_N."_conversation_label_to_object
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditions->getParameters());
		$data = array();
		while ($row = $statement->fetchArray()) {
			if (!isset($data[$row['conversationID']])) {
				$data[$row['conversationID']] = array();
			}
			
			$data[$row['conversationID']][$row['labelID']] = $labels[$row['labelID']];
		}
		
		return $data;
	}
}