<?php

namespace wcf\data\conversation;

use wcf\data\CollectionDatabaseObject;
use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\message\ViewableConversationMessage;
use wcf\data\conversation\participant\ConversationParticipant;
use wcf\data\IPopoverObject;
use wcf\data\user\group\UserGroup;
use wcf\data\user\ignore\UserIgnore;
use wcf\data\user\UserProfile;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\conversation\ConversationHandler;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\UserInputException;
use wcf\system\file\processor\ImageData;
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
 * @property-read   int|null $leftByOwnChoice
 *
 * @extends CollectionDatabaseObject<ConversationCollection>
 */
class Conversation extends CollectionDatabaseObject implements IPopoverObject, IRouteController
{
    /**
     * default participation state
     * @var int
     */
    public const STATE_DEFAULT = 0;

    /**
     * conversation is hidden but returns visible upon new message
     * @var int
     */
    public const STATE_HIDDEN = 1;

    /**
     * conversation was left permanently
     * @var int
     */
    public const STATE_LEFT/*4DEAD*/ = 2;

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return $this->subject;
    }

    /**
     * @inheritDoc
     */
    public function getLink(): string
    {
        return LinkHandler::getInstance()->getLink('Conversation', [
            'object' => $this,
            'forceFrontend' => true,
        ]);
    }

    /**
     * Returns true if this conversation is new for the active user.
     */
    public function isNew(): bool
    {
        if (!$this->isDraft && $this->lastPostTime > $this->lastVisitTime) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if the active user doesn't have read the given message.
     */
    public function isNewMessage(ConversationMessage|ViewableConversationMessage $message): bool
    {
        if (!$this->isDraft && $message->time > $this->lastVisitTime) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if the conversation is not closed or the user was not removed.
     */
    public function canReply(): bool
    {
        if (!$this->canRead()) {
            return false;
        }

        return !$this->isClosed && !$this->leftAt && WCF::getSession()->getPermission('user.conversation.canReplyToConversation');
    }

    /**
     * Overrides the last message data, used when `leftAt < lastPostTime`.
     *
     * @return void
     */
    public function setLastMessage(?int $userID, string $username, int $time)
    {
        $this->data['lastPostTime'] = $time;
        $this->data['lastPosterID'] = $userID;
        $this->data['lastPoster'] = $username;
    }

    /**
     * @since 6.2
     */
    public function getTeaser(): string
    {
        if (!$this->canReadFirstMessage()) {
            return '';
        }

        return $this->getFirstMessage()?->getTeaser() ?? '';
    }

    /**
     * @since 6.2
     */
    public function getTeaserImage(): ?ImageData
    {
        if (!$this->canReadFirstMessage()) {
            return null;
        }

        return $this->getFirstMessage()?->getTeaserImage();
    }

    /**
     * Returns a specific user conversation.
     *
     * @deprecated 6.2 Use `Conversation::getParticipant()` or `Conversation::getOtherParticipant()` instead.
     */
    public static function getUserConversation(int $conversationID, int $userID): ?Conversation
    {
        $sql = "SELECT      conversation_to_user.*, conversation.*
                FROM        wcf1_conversation conversation
                LEFT JOIN   wcf1_conversation_to_user conversation_to_user
                ON          conversation_to_user.participantID = ?
                        AND conversation_to_user.conversationID = conversation.conversationID
                WHERE       conversation.conversationID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$userID, $conversationID]);
        $row = $statement->fetchArray();
        if ($row !== false) {
            return new self(null, $row);
        }

        return null;
    }

    /**
     * Returns a list of user conversations.
     *
     * @param int[] $conversationIDs
     * @return array<int, Conversation>
     * @deprecated 6.2
     */
    public static function getUserConversations(array $conversationIDs, int $userID): array
    {
        $conditionBuilder = new PreparedStatementConditionBuilder();
        $conditionBuilder->add('conversation.conversationID IN (?)', [$conversationIDs]);
        $sql = "SELECT      conversation_to_user.*, conversation.*
                FROM        wcf1_conversation conversation
                LEFT JOIN   wcf1_conversation_to_user conversation_to_user
                ON          conversation_to_user.participantID = " . $userID . "
                        AND conversation_to_user.conversationID = conversation.conversationID
                " . $conditionBuilder;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditionBuilder->getParameters());
        $conversations = [];
        while ($row = $statement->fetchArray()) {
            $conversations[$row['conversationID']] = new self(null, $row);
        }

        return $conversations;
    }

    /**
     * Returns true if the active user has the permission to read this conversation.
     */
    public function canRead(): bool
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
     */
    public function canAddParticipants(): bool
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
     */
    public function canAddParticipantsUnrestricted(): bool
    {
        if ($this->joinedAt === null) {
            throw new \RuntimeException("Data not available, conversation must be fetched through `UserConversationList`.");
        }

        return $this->joinedAt === 0;
    }

    /**
     * Returns true if the given participant is permitted to read the first message
     * of this conversation. Participants that joined at a later point must not see
     * the messages that were written before they joined.
     *
     * @since 6.2
     */
    public function canReadFirstMessage(?int $userID = null): bool
    {
        if ($userID === null || $userID === WCF::getUser()->userID) {
            $joinedAt = $this->joinedAt;
        } else {
            $joinedAt = $this->getOtherParticipant($userID)?->joinedAt;
        }

        // Drafts have no participants at all and conversations that were not fetched
        // through `UserConversationList` do not carry a join time. Both cases offer no
        // restriction to apply, the read access itself is enforced by the callers.
        return ($joinedAt ?? 0) === 0;
    }

    public function getFirstMessage(): ?ConversationMessage
    {
        return $this->getCollection()->getFirstMessage($this);
    }

    /**
     * Returns a list of the ids of all participants.
     *
     * @return int[]
     */
    public function getParticipantIDs(bool $excludeLeftParticipants = false): array
    {
        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add("conversationID = ?", [$this->conversationID]);
        $conditions->add("participantID IS NOT NULL");
        if ($excludeLeftParticipants) {
            $conditions->add("(hideConversation <> ? AND leftAt = ?)", [self::STATE_LEFT, 0]);
        }

        $sql = "SELECT  participantID
                FROM    wcf1_conversation_to_user
                " . $conditions;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditions->getParameters());

        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Returns a list of the usernames of all participants.
     *
     * @return string[]
     */
    public function getParticipantNames(bool $excludeSelf = false, bool $leftByOwnChoice = false, bool $isAuthor = false): array
    {
        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add("conversationID = ?", [$this->conversationID]);
        if ($excludeSelf) {
            $conditions->add("conversation_to_user.participantID <> ?", [WCF::getUser()->userID]);
        }
        if ($leftByOwnChoice) {
            $conditions->add("conversation_to_user.leftByOwnChoice = ?", [1]);
        }
        if (!$isAuthor) {
            $conditions->add("conversation_to_user.isInvisible = ?", [0]);
        }

        $sql = "SELECT      user_table.username
                FROM        wcf1_conversation_to_user conversation_to_user
                LEFT JOIN   wcf1_user user_table
                ON          user_table.userID = conversation_to_user.participantID
                " . $conditions;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditions->getParameters());

        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Returns false if the active user is the last participant of this conversation.
     *
     * @deprecated 6.2 No longer in use.
     */
    public function hasOtherParticipants(): bool
    {
        $participantList = new ConversationParticipantList(
            $this->conversationID,
            WCF::getUser()->userID,
            $this->userID == WCF::getUser()->userID
        );
        $participantList->getConditionBuilder()->add('conversation_to_user.hideConversation <> ?', [self::STATE_LEFT]);
        $participantList->getConditionBuilder()->add('conversation_to_user.leftAt = ?', [0]);
        $participantList->readObjectIDs();

        return \count($participantList->getObjectIDs()) > 1;
    }

    /**
     * Returns true if the current user is an active participant of this conversation.
     */
    public function isActiveParticipant(): bool
    {
        if ($this->leftAt === null) {
            throw new \RuntimeException("Data not available, conversation must be fetched through `UserConversationList`.");
        }

        return $this->leftAt === 0;
    }

    /**
     * @inheritDoc
     */
    public function getPopoverLinkClass(): string
    {
        return 'conversationLink';
    }

    /**
     * Returns true if the given user id (default: current user) is participant
     * of all given conversation ids.
     *
     * @param int[] $conversationIDs
     */
    public static function isParticipant(array $conversationIDs, ?int $userID = null): bool
    {
        if ($userID === null) {
            $userID = WCF::getUser()->userID;
        }

        // Check if the user is the initial author. Drafts have no rows in
        // `wcf1_conversation_to_user`, therefore a missing row is treated as
        // an active participation.
        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add("conversation.conversationID IN (?)", [$conversationIDs]);
        $conditions->add("conversation.userID = ?", [$userID]);
        $conditions->add(
            "(conversation_to_user.hideConversation IS NULL OR conversation_to_user.hideConversation <> ?)",
            [self::STATE_LEFT]
        );

        $sql = "SELECT      conversation.conversationID
                FROM        wcf1_conversation conversation
                LEFT JOIN   wcf1_conversation_to_user conversation_to_user
                ON          conversation_to_user.conversationID = conversation.conversationID
                        AND conversation_to_user.participantID = conversation.userID
                " . $conditions;
        $statement = WCF::getDB()->prepare($sql);
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
                    FROM    wcf1_conversation_to_user
                    " . $conditions;
            $statement = WCF::getDB()->prepare($sql);
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
     * @param string[]|string $participants
     * @param int[] $existingParticipants
     * @return list<int>
     * @throws UserInputException
     * @deprecated 6.2 No longer in use.
     */
    public static function validateParticipants(
        array|string $participants,
        string $field = 'participants',
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
     * @param string[]|string $participants
     * @param int[] $existingParticipants
     * @return list<int>
     * @deprecated 6.2 No longer in use.
     */
    public static function validateGroupParticipants(
        array|string $participants,
        string $field = 'participants',
        array $existingParticipants = []
    ) {
        $groupIDs = \is_array($participants) ? $participants : ArrayUtil::toIntegerArray(\explode(',', $participants));
        $validGroupIDs = [];
        $result = [];

        foreach ($groupIDs as $groupID) {
            $group = UserGroup::getGroupByID($groupID);
            // @phpstan-ignore property.notFound
            if ($group !== null && $group->canBeAddedAsConversationParticipant) {
                $validGroupIDs[] = $groupID;
            }
        }

        if (!empty($validGroupIDs)) {
            $userIDs = [];
            $conditionBuilder = new PreparedStatementConditionBuilder();
            $conditionBuilder->add('groupID IN (?)', [$validGroupIDs]);
            $sql = "SELECT  DISTINCT userID
                    FROM    wcf1_user_to_group
                    " . $conditionBuilder;
            $statement = WCF::getDB()->prepare($sql);
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
     * @return void
     * @throws UserInputException
     * @deprecated 6.2 Use `TConversationForm::getParticipantsValidator()` instead.
     */
    public static function validateParticipant(UserProfile $user, string $field = 'participants')
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

    /**
     * @since 6.2
     */
    public function getUserProfile(): UserProfile
    {
        return $this->getCollection()->getUserProfile($this);
    }

    /**
     * @since 6.2
     */
    public function getLastPosterProfile(): UserProfile
    {
        return $this->getCollection()->getLastPosterProfile($this);
    }

    /**
     * @return array<int, ConversationLabel>
     * @since 6.2
     */
    public function getAssignedLabels(): array
    {
        return $this->getCollection()->getAssignedLabels($this);
    }

    /**
     * @return list<UserProfile>
     * @since 6.2
     */
    public function getParticipantSummary(): array
    {
        return $this->getCollection()->getParticipantSummary($this);
    }

    /**
     * Provides information about the active user's participation in this conversation.
     * Returns null if the active user is not a participant in this conversation.
     *
     * @since 6.2
     */
    public function getParticipant(): ?ConversationParticipant
    {
        return $this->getCollection()->getParticipant($this);
    }

    /**
     * Provides information about the given user's participation in this conversation.
     * Returns null if the given user is not a participant in this conversation.
     *
     * @since 6.2
     */
    public function getOtherParticipant(int $userID): ?ConversationParticipant
    {
        return ConversationParticipant::getParticipant($this->conversationID, $userID);
    }

    #[\Override]
    public function __get($name)
    {
        return match ($name) {
            'participantID', 'hideConversation', 'isInvisible', 'lastVisitTime',
            'joinedAt', 'leftAt', 'lastMessageID', 'leftByOwnChoice' => $this->getParticipantData($name),
            default => parent::__get($name),
        };
    }

    private function getParticipantData(string $name): mixed
    {
        if (\array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        return $this->getParticipant()?->$name;
    }
}
