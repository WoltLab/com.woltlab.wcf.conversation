<?php

namespace wcf\data\conversation;

use wcf\data\conversation\message\ConversationMessage;
use wcf\data\DatabaseObject;
use wcf\data\IPopoverObject;
use wcf\data\user\group\UserGroup;
use wcf\data\user\ignore\UserIgnore;
use wcf\data\user\UserProfile;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
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
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package WoltLabSuite\Core\Data\Conversation
 *
 * @property-read   int $conversationID     unique id of the conversation
 * @property-read   string $subject        subject of the conversation
 * @property-read   int $time           timestamp at which the conversation has been started
 * @property-read   int $firstMessageID     id of the first conversation message
 * @property-read   int|null $userID         id of the user who started the conversation or `null` if the user does not exist anymore
 * @property-read   string $username       name of the user who started the conversation
 * @property-read   int $lastPostTime       timestamp at which the conversation's last message has been written
 * @property-read   int|null $lastPosterID       id of the user who wrote the conversation's last message or `null` if the user does not exist anymore
 * @property-read   string $lastPoster     name of the user who wrote the conversation's last message
 * @property-read   int $replies        number of replies on the conversation
 * @property-read   int $attachments        total number of attachments in all messages of the conversation
 * @property-read   int $participants       number of participants of the conversations
 * @property-read   string $participantSummary serialized data of five of the conversation participants (sorted by username)
 * @property-read   int $participantCanInvite   is `1` if participants can invite other users to join the conversation, otherwise `0`
 * @property-read   int $isClosed       is `1` if the conversation is closed for new messages, otherwise `0`
 * @property-read   int $isDraft        is `1` if the conversation is a draft only, thus not sent to any participant, otherwise `0`
 * @property-read   string $draftData      serialized ids of the participants and invisible participants if conversation is a draft, otherwise `0`
 * @property-read   int|null $participantID      id of the user whose conversations are fetched via `UserConversationList`, otherwise `null`
 * @property-read   int|null $hideConversation   is `1` if the user has hidden conversation, otherwise `0`; is `null` if the conversation has not been fetched via `UserConversationList`
 * @property-read   int|null $isInvisible        is `1` if the user is invisible in conversation, otherwise `0`; is `null` if the conversation has not been fetched via `UserConversationList`
 * @property-read   int|null $lastVisitTime      timestamp at which the user last visited the conversation after a new messsage had been written or `0` if they have not visited it at all; is `null` if the conversation has not been fetched via `UserConversationList`
 * @property-read   int|null $joinedAt       timestamp at which the user joined the conversation; is `null` if the conversation has not been fetched via `UserConversationList`
 * @property-read   int|null $leftAt         timestamp at which the user left the conversation or `0` if they did not leave the conversation; is `null` if the conversation has not been fetched via `UserConversationList`
 * @property-read   int|null $lastMessageID      id of the last message written before the user left the conversation or `0` if they did not leave the conversation; is `null` if the conversation has not been fetched via `UserConversationList`
 */
class Conversation extends DatabaseObject implements IPopoverObject, IRouteController
{
    /**
     * default participation state
     * @var int
     */
    const STATE_DEFAULT = 0;

    /**
     * conversation is hidden but returns visible upon new message
     * @var int
     */
    const STATE_HIDDEN = 1;

    /**
     * conversation was left permanently
     * @var int
     */
    const STATE_LEFT/*4DEAD*/ = 2;

    /**
     * true if the current user can add users without limitations
     * @var bool
     */
    protected $canAddUnrestricted;

    /**
     * first message object
     * @var ConversationMessage
     */
    protected $firstMessage;

    /**
     * true if the current user is an active participant of this conversation
     * @var bool
     */
    protected $isActiveParticipant;

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return $this->subject;
    }

    /**
     * @inheritDoc
     */
    public function getLink()
    {
        return LinkHandler::getInstance()->getLink('Conversation', [
            'object' => $this,
            'forceFrontend' => true,
        ]);
    }

    /**
     * Returns true if this conversation is new for the active user.
     *
     * @return  bool
     */
    public function isNew()
    {
        if (!$this->isDraft && $this->lastPostTime > $this->lastVisitTime) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if the active user doesn't have read the given message.
     *
     * @param ConversationMessage $message
     * @return  bool
     */
    public function isNewMessage(ConversationMessage $message)
    {
        if (!$this->isDraft && $message->time > $this->lastVisitTime) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if the conversation is not closed or the user was not removed.
     *
     * @return      bool
     */
    public function canReply()
    {
        return !$this->isClosed && !$this->leftAt && WCF::getSession()->getPermission('user.conversation.canReplyToConversation');
    }

    /**
     * Overrides the last message data, used when `leftAt < lastPostTime`.
     *
     * @param int $userID
     * @param string $username
     * @param int $time
     */
    public function setLastMessage($userID, $username, $time)
    {
        $this->data['lastPostTime'] = $time;
        $this->data['lastPosterID'] = $userID;
        $this->data['lastPoster'] = $username;
    }

    /**
     * Loads participation data for given user id (default: current user) on runtime.
     * You should use Conversation::getUserConversation() instead if possible.
     *
     * @param int $userID
     */
    public function loadUserParticipation($userID = null)
    {
        if ($userID === null) {
            $userID = WCF::getUser()->userID;
        }

        $sql = "SELECT  *
                FROM    wcf" . WCF_N . "_conversation_to_user
                WHERE   participantID = ?
                    AND conversationID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$userID, $this->conversationID]);
        $row = $statement->fetchArray();
        if ($row !== false) {
            $this->data = \array_merge($this->data, $row);
        }
    }

    /**
     * Returns a specific user conversation.
     *
     * @param int $conversationID
     * @param int $userID
     * @return  Conversation
     */
    public static function getUserConversation($conversationID, $userID)
    {
        $sql = "SELECT      conversation_to_user.*, conversation.*
                FROM        wcf" . WCF_N . "_conversation conversation
                LEFT JOIN   wcf" . WCF_N . "_conversation_to_user conversation_to_user
                ON          conversation_to_user.participantID = ?
                        AND conversation_to_user.conversationID = conversation.conversationID
                WHERE       conversation.conversationID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$userID, $conversationID]);
        $row = $statement->fetchArray();
        if ($row !== false) {
            return new self(null, $row);
        }
    }

    /**
     * Returns a list of user conversations.
     *
     * @param int[] $conversationIDs
     * @param int $userID
     * @return  Conversation[]
     */
    public static function getUserConversations(array $conversationIDs, $userID)
    {
        $conditionBuilder = new PreparedStatementConditionBuilder();
        $conditionBuilder->add('conversation.conversationID IN (?)', [$conversationIDs]);
        $sql = "SELECT      conversation_to_user.*, conversation.*
                FROM        wcf" . WCF_N . "_conversation conversation
                LEFT JOIN   wcf" . WCF_N . "_conversation_to_user conversation_to_user
                ON          conversation_to_user.participantID = " . $userID . "
                        AND conversation_to_user.conversationID = conversation.conversationID
                " . $conditionBuilder;
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($conditionBuilder->getParameters());
        $conversations = [];
        while ($row = $statement->fetchArray()) {
            $conversations[$row['conversationID']] = new self(null, $row);
        }

        return $conversations;
    }

    /**
     * Returns true if the active user has the permission to read this conversation.
     *
     * @return  bool
     */
    public function canRead()
    {
        if (!WCF::getUser()->userID) {
            return false;
        }

        if ($this->isDraft && $this->userID == WCF::getUser()->userID) {
            return true;
        }

        if ($this->participantID == WCF::getUser()->userID && $this->hideConversation != self::STATE_LEFT) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if the current user can add new participants to this conversation.
     *
     * @return  bool
     */
    public function canAddParticipants()
    {
        if ($this->isDraft) {
            return false;
        }

        // check permissions
        if (WCF::getUser()->userID != $this->userID) {
            if (
                !$this->participantCanInvite
                && !WCF::getSession()->getPermission('mod.conversation.canAlwaysInviteUsers')
            ) {
                return false;
            }
        }

        // check for maximum number of participants
        // note: 'participants' does not track invisible participants, this will be checked on the fly!
        if ($this->participants >= WCF::getSession()->getPermission('user.conversation.maxParticipants')) {
            return false;
        }

        if (!$this->isActiveParticipant()) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if the current user can add participants without limitations.
     *
     * @return      bool
     */
    public function canAddParticipantsUnrestricted()
    {
        if ($this->canAddUnrestricted === null) {
            $this->canAddUnrestricted = false;
            if ($this->isActiveParticipant()) {
                $sql = "SELECT  joinedAt
                        FROM    wcf" . WCF_N . "_conversation_to_user
                        WHERE   conversationID = ?
                            AND participantID = ?";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute([
                    $this->conversationID,
                    WCF::getUser()->userID,
                ]);
                $joinedAt = $statement->fetchSingleColumn();

                if ($joinedAt !== false && $joinedAt == 0) {
                    $this->canAddUnrestricted = true;
                }
            }
        }

        return $this->canAddUnrestricted;
    }

    /**
     * Returns the first message in this conversation.
     *
     * @return  ConversationMessage
     */
    public function getFirstMessage()
    {
        if ($this->firstMessage === null) {
            $this->firstMessage = new ConversationMessage($this->firstMessageID);
        }

        return $this->firstMessage;
    }

    /**
     * Sets the first message.
     *
     * @param ConversationMessage $message
     */
    public function setFirstMessage(ConversationMessage $message)
    {
        $this->firstMessage = $message;
    }

    /**
     * Returns a list of the ids of all participants.
     *
     * @param bool $excludeLeftParticipants
     * @return  int[]
     */
    public function getParticipantIDs($excludeLeftParticipants = false)
    {
        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add("conversationID = ?", [$this->conversationID]);
        $conditions->add("participantID IS NOT NULL");
        if ($excludeLeftParticipants) {
            $conditions->add("(hideConversation <> ? AND leftAt = ?)", [self::STATE_LEFT, 0]);
        }

        $sql = "SELECT  participantID
                FROM    wcf" . WCF_N . "_conversation_to_user
                " . $conditions;
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($conditions->getParameters());

        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Returns a list of the usernames of all participants.
     *
     * @param bool $excludeSelf
     * @param bool $leftByOwnChoice
     * @return  string[]
     */
    public function getParticipantNames($excludeSelf = false, $leftByOwnChoice = false)
    {
        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add("conversationID = ?", [$this->conversationID]);
        if ($excludeSelf) {
            $conditions->add("conversation_to_user.participantID <> ?", [WCF::getUser()->userID]);
        }
        if ($leftByOwnChoice) {
            $conditions->add("conversation_to_user.leftByOwnChoice = ?", [1]);
        }

        $sql = "SELECT      user_table.username
                FROM        wcf" . WCF_N . "_conversation_to_user conversation_to_user
                LEFT JOIN   wcf" . WCF_N . "_user user_table
                ON          user_table.userID = conversation_to_user.participantID
                " . $conditions;
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($conditions->getParameters());

        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Returns false if the active user is the last participant of this conversation.
     *
     * @return  bool
     */
    public function hasOtherParticipants()
    {
        if ($this->userID == WCF::getUser()->userID) {
            // author
            if ($this->participants == 0) {
                return false;
            }

            return true;
        } else {
            if ($this->participants > 1) {
                return true;
            }
            if ($this->isInvisible && $this->participants > 0) {
                return true;
            }

            if ($this->userID) {
                // check if author has left the conversation
                $sql = "SELECT  hideConversation
                        FROM    wcf" . WCF_N . "_conversation_to_user
                        WHERE   conversationID = ?
                            AND participantID = ?";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute([$this->conversationID, $this->userID]);
                $row = $statement->fetchArray();
                if ($row !== false) {
                    if ($row['hideConversation'] != self::STATE_LEFT) {
                        return true;
                    }
                }
            }

            return false;
        }
    }

    /**
     * Returns true if the current user is an active participant of this conversation.
     *
     * @return      bool
     */
    public function isActiveParticipant()
    {
        if ($this->isActiveParticipant === null) {
            $sql = "SELECT  leftAt
                    FROM    wcf" . WCF_N . "_conversation_to_user
                    WHERE   conversationID = ?
                        AND participantID = ?";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute([
                $this->conversationID,
                WCF::getUser()->userID,
            ]);
            $leftAt = $statement->fetchSingleColumn();

            $this->isActiveParticipant = ($leftAt !== false && $leftAt == 0);
        }

        return $this->isActiveParticipant;
    }

    /**
     * @inheritDoc
     */
    public function getPopoverLinkClass()
    {
        return 'conversationLink';
    }

    /**
     * Returns true if the given user id (default: current user) is participant
     * of all given conversation ids.
     *
     * @param int[] $conversationIDs
     * @param int $userID
     * @return  bool
     */
    public static function isParticipant(array $conversationIDs, $userID = null)
    {
        if ($userID === null) {
            $userID = WCF::getUser()->userID;
        }

        // check if user is the initial author
        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add("conversationID IN (?)", [$conversationIDs]);
        $conditions->add("userID = ?", [$userID]);

        $sql = "SELECT  conversationID
                FROM    wcf" . WCF_N . "_conversation
                " . $conditions;
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($conditions->getParameters());
        while ($row = $statement->fetchArray()) {
            $index = \array_search($row['conversationID'], $conversationIDs);
            unset($conversationIDs[$index]);
        }

        // check for participation
        if (!empty($conversationIDs)) {
            $conditions = new PreparedStatementConditionBuilder();
            $conditions->add("conversationID IN (?)", [$conversationIDs]);
            $conditions->add("participantID = ?", [$userID]);
            $conditions->add("hideConversation <> ?", [self::STATE_LEFT]);

            $sql = "SELECT  conversationID
                    FROM    wcf" . WCF_N . "_conversation_to_user
                    " . $conditions;
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($conditions->getParameters());
            while ($row = $statement->fetchArray()) {
                $index = \array_search($row['conversationID'], $conversationIDs);
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
     * @param mixed $participants
     * @param string $field
     * @param int[] $existingParticipants
     * @return  array       $result
     * @throws  UserInputException
     */
    public static function validateParticipants(
        $participants,
        $field = 'participants',
        array $existingParticipants = []
    ) {
        $result = [];
        $error = [];

        // loop through participants and check their settings
        $participantList = UserProfile::getUserProfilesByUsername(
            (\is_array($participants) ? $participants : ArrayUtil::trim(\explode(',', $participants)))
        );

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
                } elseif (\in_array($user->userID, $existingParticipants)) {
                    throw new UserInputException($field, 'duplicate');
                }

                // validate user
                self::validateParticipant($user, $field);

                // no error
                $existingParticipants[] = $result[] = $user->userID;
            } catch (UserInputException $e) {
                $error[] = ['type' => $e->getType(), 'username' => $participant];
            }
        }

        if (!empty($error)) {
            throw new UserInputException($field, $error);
        }

        return $result;
    }

    /**
     * Validates the group participants.
     *
     * @param mixed $participants
     * @param string $field
     * @param int[] $existingParticipants
     * @return  array       $result
     */
    public static function validateGroupParticipants(
        $participants,
        $field = 'participants',
        array $existingParticipants = []
    ) {
        $groupIDs = \is_array($participants) ? $participants : ArrayUtil::toIntegerArray(\explode(',', $participants));
        $validGroupIDs = [];
        $result = [];

        foreach ($groupIDs as $groupID) {
            $group = UserGroup::getGroupByID($groupID);
            /** @noinspection PhpUndefinedFieldInspection */
            if ($group !== null && $group->canBeAddedAsConversationParticipant) {
                $validGroupIDs[] = $groupID;
            }
        }

        if (!empty($validGroupIDs)) {
            $userIDs = [];
            $conditionBuilder = new PreparedStatementConditionBuilder();
            $conditionBuilder->add('groupID IN (?)', [$validGroupIDs]);
            $sql = "SELECT  DISTINCT userID
                    FROM    wcf" . WCF_N . "_user_to_group
                    " . $conditionBuilder;
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($conditionBuilder->getParameters());
            while ($userID = $statement->fetchColumn()) {
                $userIDs[] = $userID;
            }

            if (!empty($userIDs)) {
                $users = UserProfileRuntimeCache::getInstance()->getObjects($userIDs);
                UserStorageHandler::getInstance()->loadStorage($userIDs);

                foreach ($users as $user) {
                    // user is author
                    if ($user->userID == WCF::getUser()->userID) {
                        continue;
                    } elseif (\in_array($user->userID, $existingParticipants)) {
                        continue;
                    }

                    try {
                        // validate user
                        self::validateParticipant($user, $field);

                        // no error
                        $result[] = $user->userID;
                    } catch (UserInputException $e) {
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Validates the given participant.
     *
     * @param UserProfile $user
     * @param string $field
     * @throws  UserInputException
     */
    public static function validateParticipant(UserProfile $user, $field = 'participants')
    {
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
            if ($user->isIgnoredUser(WCF::getUser()->userID, UserIgnore::TYPE_BLOCK_DIRECT_CONTACT)) {
                throw new UserInputException($field, 'ignoresYou');
            }

            // check participant's mailbox quota
            if (ConversationHandler::getInstance()->getConversationCount($user->userID) >= $user->getPermission('user.conversation.maxConversations')) {
                throw new UserInputException($field, 'mailboxIsFull');
            }
        }
    }
}
