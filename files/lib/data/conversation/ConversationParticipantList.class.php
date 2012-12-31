<?php 
namespace wcf\data\conversation;
use wcf\data\user\UserProfileList;
use wcf\system\WCF;

/**
 * Represents a list of conversation participants.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation
 * @category	Community Framework
 */
class ConversationParticipantList extends UserProfileList {
	/**
	 * @see	wcf\data\DatabaseObjectList::$sqlLimit
	 */
	public $sqlLimit = 0;
	
	/**
	 * Creates a new ConversationParticipantList object.
	 * 
	 * @param	integer		$conversationID
	 */
	public function __construct($conversationID, $userID = 0) {
		parent::__construct();
		
		$this->getConditionBuilder()->add('conversation_to_user.conversationID = ?', array($conversationID));
		if ($userID) {
			$this->getConditionBuilder()->add('conversation_to_user.isInvisible = 0 OR conversation_to_user.participantID = ?', array($userID));
		}
		else {
			$this->getConditionBuilder()->add('conversation_to_user.isInvisible = 0');	
		}
		$this->sqlConditionJoins .= " LEFT JOIN wcf".WCF_N."_user user_table ON (user_table.userID = conversation_to_user.participantID)";
		
		if (!empty($this->sqlSelects)) $this->sqlSelects .= ',';
		$this->sqlSelects = 'conversation_to_user.*';
		$this->sqlJoins .= " LEFT JOIN wcf".WCF_N."_conversation_to_user conversation_to_user ON (conversation_to_user.participantID = user_table.userID AND conversation_to_user.conversationID = ".$conversationID.")";
	}
	
	/**
	 * @see	wcf\data\DatabaseObjectList::countObjects()
	 */
	public function countObjects() {
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
		$this->objectIDs = array();
		$sql = "SELECT	conversation_to_user.participantID AS objectID
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
}
