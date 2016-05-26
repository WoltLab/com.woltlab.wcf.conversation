<?php
namespace wcf\data\conversation;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\user\UserProfile;
use wcf\data\DatabaseObject;
use wcf\data\ITitledLinkObject;
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
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation
 * @category	Community Framework
 * 
 * @property-read	integer		$conversationID
 * @property-read	string		$subject
 * @property-read	integer		$time
 * @property-read	integer		$firstMessageID
 * @property-read	integer|null	$userID
 * @property-read	string		$username
 * @property-read	integer		$lastPostTime
 * @property-read	integer|null	$lastPosterID
 * @property-read	string		$lastPoster
 * @property-read	integer		$replies
 * @property-read	integer		$attachments
 * @property-read	integer		$participants
 * @property-read	string		$participantSummary
 * @property-read	integer		$participantCanInvite
 * @property-read	integer		$isClosed
 * @property-read	integer		$isDraft
 * @property-read	string		$draftData
 * @property-read	integer|null	$participantID
 * @property-read	integer|null	$hideConversation
 * @property-read	integer|null	$isInvisible
 * @property-read	integer|null	$lastVisitTime
 */
class Conversation extends DatabaseObject implements IRouteController, ITitledLinkObject {
	/**
	 * @inheritDoc
	 */
	protected static $databaseTableName = 'conversation';
	
	/**
	 * @inheritDoc
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
	 * @var	ConversationMessage
	 */
	protected $firstMessage = null;
	
	/**
	 * @inheritDoc
	 */
	public function getTitle() {
		return $this->subject;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getLink() {
		return LinkHandler::getInstance()->getLink('Conversation', ['object' => $this]);
	}
	
	/**
	 * Returns true if this conversation is new for the active user.
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
	 * Returns true if the active user doesn't have read the given message.
	 * 
	 * @param	ConversationMessage	$message
	 * @return	boolean
	 */
	public function isNewMessage(ConversationMessage $message) {
		if (!$this->isDraft && $message->time > $this->lastVisitTime) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Loads participation data for given user id (default: current user) on runtime.
	 * You should use Conversation::getUserConversation() instead if possible.
	 *
	 * @param	integer		$userID
	 */
	public function loadUserParticipation($userID = null) {
		if ($userID === null) {
			$userID = WCF::getUser()->userID;
		}
		
		$sql = "SELECT	*
			FROM	wcf".WCF_N."_conversation_to_user
			WHERE	participantID = ?
				AND conversationID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([$userID, $this->conversationID]);
		$row = $statement->fetchArray();
		if ($row !== false) {
			$this->data = array_merge($this->data, $row);
		}
	}
	
	/**
	 * Returns a specific user conversation.
	 * 
	 * @param	integer		$conversationID
	 * @param	integer		$userID
	 * @return	Conversation
	 */
	public static function getUserConversation($conversationID, $userID) {
		$sql = "SELECT		conversation_to_user.*, conversation.*
			FROM		wcf".WCF_N."_conversation conversation
			LEFT JOIN	wcf".WCF_N."_conversation_to_user conversation_to_user
			ON		(conversation_to_user.participantID = ? AND conversation_to_user.conversationID = conversation.conversationID)
			WHERE		conversation.conversationID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([$userID, $conversationID]);
		$row = $statement->fetchArray();
		if ($row !== false) {
			return new Conversation(null, $row);
		}
		
		return null;
	}
	
	/**
	 * Returns a list of user conversations.
	 * 
	 * @param	integer[]		$conversationIDs
	 * @param	integer			$userID
	 * @return	Conversation[]
	 */
	public static function getUserConversations(array $conversationIDs, $userID) {
		$conditionBuilder = new PreparedStatementConditionBuilder();
		$conditionBuilder->add('conversation.conversationID IN (?)', [$conversationIDs]);
		$sql = "SELECT		conversation_to_user.*, conversation.*
			FROM		wcf".WCF_N."_conversation conversation
			LEFT JOIN	wcf".WCF_N."_conversation_to_user conversation_to_user
			ON		(conversation_to_user.participantID = ".$userID." AND conversation_to_user.conversationID = conversation.conversationID)
			".$conditionBuilder;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditionBuilder->getParameters());
		$conversations = [];
		while (($row = $statement->fetchArray())) {
			$conversations[$row['conversationID']] = new Conversation(null, $row);
		}
		
		return $conversations;
	}
	
	/**
	 * Returns true if the active user has the permission to read this conversation.
	 * 
	 * @return	boolean
	 */
	public function canRead() {
		if (!WCF::getUser()->userID) return false;
		
		if ($this->isDraft && $this->userID == WCF::getUser()->userID) return true;
		
		if ($this->participantID == WCF::getUser()->userID && $this->hideConversation != self::STATE_LEFT) return true;
		
		return false;
	}
	
	/**
	 * Returns true if current user can add new participants to this conversation.
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
	 * Returns the first message in this conversation.
	 * 
	 * @return	ConversationMessage
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
	 * @param	ConversationMessage	$message
	 */
	public function setFirstMessage(ConversationMessage $message) {
		$this->firstMessage = $message;
	}
	
	/**
	 * Returns a list of the ids of all participants.
	 * 
	 * @param	boolean		$excludeLeftParticipants
	 * @return	integer[]
	 */
	public function getParticipantIDs($excludeLeftParticipants = false) {
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("conversationID = ?", [$this->conversationID]);
		if ($excludeLeftParticipants) $conditions->add("hideConversation <> ?", [self::STATE_LEFT]);
		
		$sql = "SELECT		participantID
			FROM		wcf".WCF_N."_conversation_to_user
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditions->getParameters());
		
		return $statement->fetchAll(\PDO::FETCH_COLUMN);
	}
	
	/**
	 * Returns a list of the usernames of all participants.
	 * 
	 * @return	string[]
	 */
	public function getParticipantNames() {
		$sql = "SELECT		user_table.username
			FROM		wcf".WCF_N."_conversation_to_user conversation_to_user
			LEFT JOIN	wcf".WCF_N."_user user_table
			ON		(user_table.userID = conversation_to_user.participantID)
			WHERE		conversationID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([$this->conversationID]);
		
		return $statement->fetchAll(\PDO::FETCH_COLUMN);
	}
	
	/**
	 * Returns false if the active user is the last participant of this conversation.
	 * 
	 * @return	boolean
	 */
	public function hasOtherParticipants() {
		if ($this->userID == WCF::getUser()->userID) {
			// author
			if ($this->participants == 0) return false;
			return true;
		}
		else {
			if ($this->participants > 1) return true;
			if ($this->isInvisible && $this->participants > 0) return true;
			
			if ($this->userID) {
				// check if author has left the conversation
				$sql = "SELECT	hideConversation
					FROM	wcf".WCF_N."_conversation_to_user
					WHERE	conversationID = ?
						AND participantID = ?";
				$statement = WCF::getDB()->prepareStatement($sql);
				$statement->execute([$this->conversationID, $this->userID]);
				$row = $statement->fetchArray();
				if ($row !== false) {
					if ($row['hideConversation'] != self::STATE_LEFT) return true;
				}
			}
			
			return false;
		}
	}
	
	/**
	 * Returns true if given user id (default: current user) is participant
	 * of all given conversation ids.
	 * 
	 * @param	integer[]	$conversationIDs
	 * @param	integer		$userID
	 * @return	boolean
	 */
	public static function isParticipant(array $conversationIDs, $userID = null) {
		if ($userID === null) $userID = WCF::getUser()->userID;
		
		// check if user is the initial author
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("conversationID IN (?)", [$conversationIDs]);
		$conditions->add("userID = ?", [$userID]);
		
		$sql = "SELECT	conversationID
			FROM	wcf".WCF_N."_conversation
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditions->getParameters());
		while (($row = $statement->fetchArray())) {
			$index = array_search($row['conversationID'], $conversationIDs);
			unset($conversationIDs[$index]);
		}
		
		// check for participation
		if (!empty($conversationIDs)) {
			$conditions = new PreparedStatementConditionBuilder();
			$conditions->add("conversationID IN (?)", [$conversationIDs]);
			$conditions->add("participantID = ?", [$userID]);
			$conditions->add("hideConversation <> ?", [self::STATE_LEFT]);
			
			$sql = "SELECT	conversationID
				FROM	wcf".WCF_N."_conversation_to_user
				".$conditions;
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute($conditions->getParameters());
			while (($row = $statement->fetchArray())) {
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
	 * @param	mixed		$participants
	 * @param	string		$field
	 * @param	integer[]	$existingParticipants
	 * @return	array		$result
	 * @throws	UserInputException
	 */
	public static function validateParticipants($participants, $field = 'participants', array $existingParticipants = []) {
		$result = [];
		$error = [];
		
		// loop through participants and check their settings
		$participantList = UserProfile::getUserProfilesByUsername((is_array($participants) ? $participants : ArrayUtil::trim(explode(',', $participants))));
		
		// load user storage at once to avoid multiple queries
		$userIDs = [];
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
				$error[] = ['type' => $e->getType(), 'username' => $participant];
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
	 * @param	UserProfile	$user
	 * @param	string		$field
	 * @throws	UserInputException
	 */
	public static function validateParticipant(UserProfile $user, $field = 'participants') {
		// check participant's settings and permissions
		if (!$user->getPermission('user.conversation.canUseConversation')) {
			throw new UserInputException($field, 'canNotUseConversation');
		}
		
		if (!WCF::getSession()->getPermission('user.profile.cannotBeIgnored')) {
			// check if user wants to receive any conversations
			/** @noinspection PhpUndefinedFieldInspection */
			if ($user->canSendConversation == 2) {
				throw new UserInputException($field, 'doesNotAcceptConversation');
			}
			
			// check if user only wants to receive conversations by
			// users they are following and if the active user is followed
			// by the relevant user
			/** @noinspection PhpUndefinedFieldInspection */
			if ($user->canSendConversation == 1 && !$user->isFollowing(WCF::getUser()->userID)) {
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
