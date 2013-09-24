<?php
namespace wcf\data\conversation;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\user\UserProfile;
use wcf\data\DatabaseObject;
use wcf\system\breadcrumb\Breadcrumb;
use wcf\system\breadcrumb\IBreadcrumbProvider;
use wcf\system\conversation\ConversationHandler;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\UserInputException;
use wcf\system\request\IRouteController;
use wcf\system\request\LinkHandler;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;
use wcf\util\ArrayUtil;

/**
 * Represents a conversation.
 * 
 * @author	Marcel Werk
 * @copyright	2009-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation
 * @category	Community Framework
 */
class Conversation extends DatabaseObject implements IBreadcrumbProvider, IRouteController {
	/**
	 * @see	wcf\data\DatabaseObject::$databaseTableName
	 */
	protected static $databaseTableName = 'conversation';
	
	/**
	 * @see	wcf\data\DatabaseObject::$databaseIndexName
	 */
	protected static $databaseTableIndexName = 'conversationID';
	
	/**
	 * default participation state
	 * @var	integer
	 */
	const STATE_DEFAULT = 0;
	
	/**
	 * conversation is hidden but returns visible upon new message
	 * @var	integer
	 */
	const STATE_HIDDEN = 1;
	
	/**
	 * conversation was left permanently
	 * @var	integer
	 */
	const STATE_LEFT/*4DEAD*/ = 2;
	
	/**
	 * first message object
	 * @var wcf\data\conversation\message\ConversationMessage
	 */
	protected $firstMessage = null;
	
	/**
	 * @see	wcf\system\request\IRouteController::getTitle()
	 */
	public function getTitle() {
		return $this->subject;
	}
	
	/**
	 * @see	wcf\system\breadcrumb\IBreadcrumbProvider::getBreadcrumb()
	 */
	public function getBreadcrumb() {
		return new Breadcrumb($this->subject, LinkHandler::getInstance()->getLink('Conversation', array(
			'object' => $this
		)));
	}
	
	/**
	 * Returns true, if this conversation is new for the active user.
	 *
	 * @return	boolean
	 */
	public function isNew() {
		if (!$this->isDraft && $this->lastPostTime > $this->lastVisitTime) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Returns true, if the active user doesn't have read the given message.
	 * 
	 * @param	wcf\data\conversation\message\ConversationMessage	$message
	 * @return	boolean
	 */
	public function isNewMessage(ConversationMessage $message) {
		if (!$this->isDraft && $message->time > $this->lastVisitTime) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Gets a specific user conversation.
	 * 
	 * @param	integer		$conversationID
	 * @param	integer		$userID
	 * @return	wcf\data\conversation\ViewableConversation
	 */
	public static function getUserConversation($conversationID, $userID) {
		$sql = "SELECT 		conversation_to_user.*, conversation.*
			FROM		wcf".WCF_N."_conversation conversation
			LEFT JOIN	wcf".WCF_N."_conversation_to_user conversation_to_user
			ON		(conversation_to_user.participantID = ? AND conversation_to_user.conversationID = conversation.conversationID)
			WHERE		conversation.conversationID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array($userID, $conversationID));
		$row = $statement->fetchArray();
		if ($row !== false) {
			return new Conversation(null, $row);
		}
		
		return null;
	}
	
	/**
	 * Returns true, if the active user has the permission to read this conversation.
	 * 
	 * @return	boolean
	 */
	public function canRead() {
		if (!WCF::getUser()->userID) return false;
		
		if ($this->isDraft && $this->userID == WCF::getUser()->userID) return true;
		
		if ($this->participantID == WCF::getUser()->userID && $this->hideConversation != 2) return true;
		
		return false;
	}
	
	/**
	 * Returns true, if current user can add new participants to this conversation.
	 * 
	 * @return	boolean
	 */
	public function canAddParticipants() {
		if ($this->isDraft) {
			return false;
		}
		
		// check permissions
		if (WCF::getUser()->userID != $this->userID && !$this->participantCanInvite) {
			return false;
		}
		
		// check for maximum number of participants
		// note: 'participants' does not track invisible participants, this will be checked on the fly!
		if ($this->participants >= WCF::getSession()->getPermission('user.conversation.maxParticipants')) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Gets the first message in this conversation.
	 * 
	 * @return	wcf\data\conversation\message\ConversationMessage
	 */
	public function getFirstMessage() {
		if ($this->firstMessage === null) {
			$this->firstMessage = new ConversationMessage($this->firstMessageID);
		}
		
		return $this->firstMessage;
	}
	
	/**
	 * Sets the first message.
	 * 
	 * @param	wcf\data\conversation\message\ConversationMessage	$message
	 */
	public function setFirstMessage(ConversationMessage $message) {
		$this->firstMessage = $message;
	}
	
	/**
	 * Gets a list of all participants.
	 * 
	 * @param	boolean		$excludeLeftParticipants
	 * @return	array<integer>
	 */
	public function getParticipantIDs($excludeLeftParticipants = false) {
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("conversationID = ?", array($this->conversationID));
		if ($excludeLeftParticipants) $conditions->add("hideConversation <> ?", array(self::STATE_LEFT));
		
		$sql = "SELECT 		participantID
			FROM		wcf".WCF_N."_conversation_to_user
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditions->getParameters());
		$participantIDs = array();
		while ($row = $statement->fetchArray()) {
			$participantIDs[] = $row['participantID'];
		}
		
		return $participantIDs;
	}
	
	/**
	 * Returns a list of participants usernames.
	 * 
	 * @return	array<string>
	 */
	public function getParticipantNames() {
		$participants = array();
		$sql = "SELECT		user_table.username
			FROM		wcf".WCF_N."_conversation_to_user conversation_to_user
			LEFT JOIN	wcf".WCF_N."_user user_table
			ON		(user_table.userID = conversation_to_user.participantID)
			WHERE		conversationID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array($this->conversationID));
		while ($row = $statement->fetchArray()) {
			$participants[] = $row['username'];
		}
		
		return $participants;
	}
	
	/**
	 * Returns true, if given user id (default: current user) is participant
	 * of all given conversation ids.
	 * 
	 * @param	array<integer>		$conversationIDs
	 * @param	integer			$userID
	 * @return	boolean
	 */
	public static function isParticipant(array $conversationIDs, $userID = null) {
		if ($userID === null) $userID = WCF::getUser()->userID;
		
		// check if user is the initial author
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("conversationID IN (?)", array($conversationIDs));
		$conditions->add("userID = ?", array($userID));
		
		$sql = "SELECT	conversationID
			FROM	wcf".WCF_N."_conversation
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditions->getParameters());
		while ($row = $statement->fetchArray()) {
			$index = array_search($row['conversationID'], $conversationIDs);
			unset($conversationIDs[$index]);
		}
		
		// check for participation
		if (!empty($conversationIDs)) {
			$conditions = new PreparedStatementConditionBuilder();
			$conditions->add("conversationID IN (?)", array($conversationIDs));
			$conditions->add("participantID = ?", array($userID));
			
			$sql = "SELECT	conversationID
				FROM	wcf".WCF_N."_conversation_to_user
				".$conditions;
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute($conditions->getParameters());
			while ($row = $statement->fetchArray()) {
				$index = array_search($row['conversationID'], $conversationIDs);
				unset($conversationIDs[$index]);
			}
		}
		
		if (!empty($conversationIDs)) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Validates the participants.
	 * 
	 * @param	string		$participants
	 * @param	string		$field
	 * @param	array<integer>	$existingParticipants
	 * @return	array		$result
	 */
	public static function validateParticipants($participants, $field = 'participants', array $existingParticipants = array()) {
		$result = array();
		$error = array();
		
		// loop through participants and check their settings
		$participantList = UserProfile::getUserProfilesByUsername(ArrayUtil::trim(explode(',', $participants)));
		
		// load user storage at once to avoid multiple queries
		$userIDs = array();
		foreach ($participantList as $user) {
			if ($user) {
				$userIDs[] = $user->userID;
			}
		}
		UserStorageHandler::getInstance()->loadStorage($userIDs);
		
		foreach ($participantList as $participant => $user) {
			try {
				if ($user === null) {
					throw new UserInputException($field, 'notFound');
				}
				
				// user is author
				if ($user->userID == WCF::getUser()->userID) {
					throw new UserInputException($field, 'isAuthor');
				}
				else if (in_array($user->userID, $existingParticipants)) {
					throw new UserInputException($field, 'duplicate');
				}
				
				// validate user
				self::validateParticipant($user, $field);
				
				// no error
				$existingParticipants[] = $result[] = $user->userID;
			}
			catch (UserInputException $e) {
				$error[] = array('type' => $e->getType(), 'username' => $participant);
			}
		}
		
		if (!empty($error)) {
			throw new UserInputException($field, $error);
		}
		
		return $result;
	}
	
	/**
	 * Validates the given participant.
	 * 
	 * @param	wcf\data\user\UserProfile	$user
	 * @param	string				$field
	 */
	public static function validateParticipant(UserProfile $user, $field = 'participants') {
		// check participant's settings and permissions
		if (!$user->getPermission('user.conversation.canUseConversation')) {
			throw new UserInputException($field, 'canNotUseConversation');
		}
		
		if (!WCF::getSession()->getPermission('user.profile.cannotBeIgnored')) {
			// check privacy setting
			if ($user->canSendConversation == 2 || ($user->canSendConversation == 1 && WCF::getUserProfileHandler()->isFollowing($user->userID))) {
				throw new UserInputException($field, 'doesNotAcceptConversation');
			}
			
			// active user is ignored by participant
			if ($user->isIgnoredUser(WCF::getUser()->userID)) {
				throw new UserInputException($field, 'ignoresYou');
			}
			
			// check participant's mailbox quota
			if (ConversationHandler::getInstance()->getConversationCount($user->userID) >= $user->getPermission('user.conversation.maxConversations')) {
				throw new UserInputException($field, 'mailboxIsFull');
			}
		}
	}
}
