<?php

namespace wcf\data\conversation;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\participant\ConversationParticipant;
use wcf\data\conversation\participant\ConversationParticipantList;
use wcf\data\DatabaseObjectCollection;
use wcf\data\user\UserProfile;
use wcf\system\cache\runtime\ConversationMessageRuntimeCache;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * Represents a collection of conversations.
 *
 * @author      Marcel Werk
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 *
 * @extends DatabaseObjectCollection<Conversation>
 */
class ConversationCollection extends DatabaseObjectCollection
{
    /**
     * @var array<int, array<int, ConversationLabel>>
     */
    private array $assignedLabels;

    /**
     * @var array<int, list<UserProfile>>
     */
    private array $participantSummaries;

    /**
     * @var array<int, ConversationParticipant>
     */
    private array $participants;

    private bool $userProfilesLoaded = false;
    private bool $firstMessagesLoaded = false;

    public function getUserProfile(Conversation $conversation): UserProfile
    {
        $this->loadUserProfiles();

        if ($conversation->userID) {
            return UserProfileRuntimeCache::getInstance()->getObject($conversation->userID);
        } else {
            return UserProfile::getGuestUserProfile($conversation->username);
        }
    }

    public function getLastPosterProfile(Conversation $conversation): UserProfile
    {
        $this->loadUserProfiles();

        if ($conversation->lastPosterID) {
            return UserProfileRuntimeCache::getInstance()->getObject($conversation->lastPosterID);
        } else {
            return UserProfile::getGuestUserProfile($conversation->lastPoster);
        }
    }

    private function loadUserProfiles(): void
    {
        if ($this->userProfilesLoaded) {
            return;
        }

        $this->userProfilesLoaded = true;

        $userIDs = [];
        foreach ($this->getObjects() as $object) {
            if ($object->userID) {
                $userIDs[] = $object->userID;
            }
            if ($object->lastPosterID) {
                $userIDs[] = $object->lastPosterID;
            }
        }

        if ($userIDs !== []) {
            UserProfileRuntimeCache::getInstance()->cacheObjectIDs($userIDs);
        }
    }

    /**
     * @return array<int, ConversationLabel>
     */
    public function getAssignedLabels(Conversation $conversation): array
    {
        $this->loadAssignedLabels();

        return $this->assignedLabels[$conversation->getObjectID()] ?? [];
    }

    private function loadAssignedLabels(): void
    {
        if (isset($this->assignedLabels)) {
            return;
        }

        $this->assignedLabels = [];
        $labels = ConversationLabel::getUserLabels();
        if ($labels === []) {
            return;
        }

        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add("conversationID IN (?)", [$this->getObjectIDs()]);
        $conditions->add("labelID IN (?)", [\array_keys($labels)]);

        $sql = "SELECT  labelID, conversationID
                FROM    wcf1_conversation_label_to_object
                " . $conditions;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditions->getParameters());
        while ($row = $statement->fetchArray()) {
            $this->assignedLabels[$row['conversationID']][$row['labelID']] = $labels[$row['labelID']];
        }
    }

    public function getFirstMessage(Conversation $conversation): ?ConversationMessage
    {
        $this->loadFirstMessages();

        return ConversationMessageRuntimeCache::getInstance()->getObject($conversation->firstMessageID);
    }

    private function loadFirstMessages(): void
    {
        if ($this->firstMessagesLoaded) {
            return;
        }

        $this->firstMessagesLoaded = true;

        $messageIDs = [];
        foreach ($this->getObjects() as $conversation) {
            if ($conversation->firstMessageID) {
                $messageIDs[] = $conversation->firstMessageID;
            }
        }

        if ($messageIDs !== []) {
            ConversationMessageRuntimeCache::getInstance()->cacheObjectIDs($messageIDs);
        }
    }

    /**
     * @return list<UserProfile>
     */
    public function getParticipantSummary(Conversation $conversation): array
    {
        $this->loadParticipantSummaries();

        return $this->participantSummaries[$conversation->getObjectID()] ?? [];
    }

    private function loadParticipantSummaries(): void
    {
        if (isset($this->participantSummaries)) {
            return;
        }

        $this->participantSummaries = [];

        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add("conversationID IN (?)", [$this->getObjectIDs()]);
        $conditions->add("participantID NOT IN (SELECT userID FROM wcf1_conversation WHERE conversationID = conversation_to_user.conversationID)");
        $conditions->add("isInvisible = ?", [0]);

        $sql = "SELECT  conversationID, userID, username
                FROM    (
                            SELECT  conversationID, participantID AS userID, username,
                                    ROW_NUMBER() OVER (PARTITION BY conversationID ORDER BY username) AS row_num
                            FROM    wcf1_conversation_to_user conversation_to_user
                            {$conditions}
                        ) AS subselect
                WHERE   subselect.row_num <= ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute(
            \array_merge($conditions->getParameters(), [5])
        );

        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            if ($row['userID']) {
                UserProfileRuntimeCache::getInstance()->cacheObjectID($row['userID']);
            }
        }
        foreach ($rows as $row) {
            if (!isset($this->participantSummaries[$row['conversationID']])) {
                $this->participantSummaries[$row['conversationID']] = [];
            }

            if ($row['userID']) {
                $this->participantSummaries[$row['conversationID']][] = UserProfileRuntimeCache::getInstance()->getObject($row['userID']);
            } else {
                $this->participantSummaries[$row['conversationID']][] = UserProfile::getGuestUserProfile($row['username']);
            }
        }
    }

    public function getParticipant(Conversation $conversation): ?ConversationParticipant
    {
        $this->loadParticipants();

        return $this->participants[$conversation->getObjectID()] ?? null;
    }

    private function loadParticipants(): void
    {
        if (isset($this->participants)) {
            return;
        }

        $this->participants = [];

        $list = new ConversationParticipantList();
        $list->getConditionBuilder()->add("conversationID IN (?)", [$this->getObjectIDs()]);
        $list->getConditionBuilder()->add("participantID = ?", [WCF::getUser()->userID]);
        $list->readObjects();

        foreach ($list->getObjects() as $participant) {
            $this->participants[$participant->conversationID] = $participant;
        }
    }
}
