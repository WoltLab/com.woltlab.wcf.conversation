<?php

namespace wcf\data\conversation;

use wcf\data\conversation\message\ConversationMessage;
use wcf\data\DatabaseObjectEditor;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * Extends the conversation object with functions to create, update and delete conversations.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @method static Conversation    create(array $parameters = [])
 * @method      Conversation    getDecoratedObject()
 * @mixin       Conversation
 */
class ConversationEditor extends DatabaseObjectEditor
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = Conversation::class;

    /**
     * Adds a new message to this conversation.
     *
     * @param ConversationMessage $message
     */
    public function addMessage(ConversationMessage $message)
    {
        $this->update([
            'lastPoster' => $message->username,
            'lastPostTime' => $message->time,
            'lastPosterID' => $message->userID,
            'replies' => $this->replies + 1,
            'attachments' => $this->attachments + $message->attachments,
        ]);
    }

    /**
     * Resets the participants of this conversation.
     */
    public function resetParticipants()
    {
        $sql = "DELETE FROM wcf" . WCF_N . "_conversation_to_user
                WHERE       conversationID = ?
                        AND participantID <> ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$this->conversationID, $this->userID]);
    }

    /**
     * Updates the participants of this conversation.
     *
     * @param int[] $participantIDs
     * @param int[] $invisibleParticipantIDs
     * @param string $visibility
     */
    public function updateParticipants(array $participantIDs, array $invisibleParticipantIDs = [], $visibility = 'all')
    {
        $usernames = [];
        if (!empty($participantIDs) || !empty($invisibleParticipantIDs)) {
            $conditions = new PreparedStatementConditionBuilder();
            $conditions->add("userID IN (?)", [\array_merge($participantIDs, $invisibleParticipantIDs)]);

            $sql = "SELECT  userID, username
                    FROM    wcf" . WCF_N . "_user
                    " . $conditions;
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($conditions->getParameters());
            while ($row = $statement->fetchArray()) {
                $usernames[$row['userID']] = $row['username'];
            }
        }

        if (!empty($participantIDs)) {
            WCF::getDB()->beginTransaction();
            $sql = "INSERT INTO wcf" . WCF_N . "_conversation_to_user
                                (conversationID, participantID, username, isInvisible, joinedAt)
                    VALUES      (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY
                    UPDATE      hideConversation = 0,
                                isInvisible = 0,
                                leftAt = 0,
                                leftByOwnChoice = 1";
            $statement = WCF::getDB()->prepareStatement($sql);

            foreach ($participantIDs as $userID) {
                $statement->execute([
                    $this->conversationID,
                    $userID,
                    $usernames[$userID],
                    0,
                    ($visibility === 'all') ? 0 : TIME_NOW,
                ]);
            }
            WCF::getDB()->commitTransaction();
        }

        if (!empty($invisibleParticipantIDs)) {
            WCF::getDB()->beginTransaction();
            $sql = "INSERT INTO     wcf" . WCF_N . "_conversation_to_user
                                    (conversationID, participantID, username, isInvisible)
                    VALUES          (?, ?, ?, ?)";
            $statement = WCF::getDB()->prepareStatement($sql);

            foreach ($invisibleParticipantIDs as $userID) {
                $statement->execute([
                    $this->conversationID,
                    $userID,
                    $usernames[$userID],
                    1,
                ]);
            }
            WCF::getDB()->commitTransaction();
        }

        $this->updateParticipantCount();
    }

    /**
     * Updates participant count.
     */
    public function updateParticipantCount()
    {
        $sql = "UPDATE  wcf" . WCF_N . "_conversation conversation
                SET     participants = (
                            SELECT  COUNT(*) AS count
                            FROM    wcf" . WCF_N . "_conversation_to_user conversation_to_user
                            WHERE   conversation_to_user.conversationID = conversation.conversationID
                                AND conversation_to_user.hideConversation <> ?
                                AND conversation_to_user.participantID <> ?
                                AND conversation_to_user.isInvisible = ?
                        )
                WHERE   conversation.conversationID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([
            Conversation::STATE_LEFT,
            $this->userID,
            0,
            $this->conversationID,
        ]);
    }

    /**
     * Updates the participant summary of this conversation.
     */
    public function updateParticipantSummary()
    {
        $sql = "SELECT      participantID AS userID, hideConversation, username
                FROM        wcf" . WCF_N . "_conversation_to_user
                WHERE       conversationID = ?
                        AND participantID <> ?
                        AND isInvisible = 0
                ORDER BY    username";
        $statement = WCF::getDB()->prepareStatement($sql, 5);
        $statement->execute([$this->conversationID, $this->userID]);

        $this->update(['participantSummary' => \serialize($statement->fetchAll(\PDO::FETCH_ASSOC))]);
    }

    /**
     * Removes a participant from this conversation.
     *
     * @param int $userID
     */
    public function removeParticipant($userID)
    {
        $sql = "SELECT  joinedAt, isInvisible
                FROM    wcf1_conversation_to_user
                WHERE   conversationID = ?
                    AND participantID = ?";
        $statement = WCF::getDB()->prepare($sql, 1);
        $statement->execute([$this->conversationID, $userID]);
        $participantData = $statement->fetchSingleRow();

        $sql = "SELECT      messageID
                FROM        wcf1_conversation_message
                WHERE       conversationID = ?
                        AND time >= ?
                        AND time <= ?
                ORDER BY    time DESC";
        $statement = WCF::getDB()->prepare($sql, 1);
        $statement->execute([
            $this->conversationID,
            $participantData['joinedAt'],
            TIME_NOW,
        ]);
        $lastMessageID = $statement->fetchSingleColumn();

        $sql = "UPDATE  wcf1_conversation_to_user
                SET     leftAt = ?,
                        lastMessageID = ?,
                        leftByOwnChoice = ?
                WHERE   conversationID = ?
                    AND participantID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([
            TIME_NOW,
            $lastMessageID ?: null,
            0,
            $this->conversationID,
            $userID,
        ]);

        // The author and invisible participants are not included in the count.
        if ($userID != $this->userID && !$participantData['isInvisible']) {
            $this->updateCounters([
                'participants' => -1,
            ]);
        }
    }

    /**
     * Updates the first message of this conversation.
     */
    public function updateFirstMessage()
    {
        $sql = "SELECT      messageID
                FROM        wcf" . WCF_N . "_conversation_message
                WHERE       conversationID = ?
                ORDER BY    time ASC";
        $statement = WCF::getDB()->prepareStatement($sql, 1);
        $statement->execute([
            $this->conversationID,
        ]);

        $this->update([
            'firstMessageID' => $statement->fetchColumn(),
        ]);
    }

    /**
     * Updates the last message of this conversation.
     */
    public function updateLastMessage()
    {
        $sql = "SELECT      time, userID, username
                FROM        wcf" . WCF_N . "_conversation_message
                WHERE       conversationID = ?
                ORDER BY    time DESC";
        $statement = WCF::getDB()->prepareStatement($sql, 1);
        $statement->execute([
            $this->conversationID,
        ]);
        $row = $statement->fetchArray();

        $this->update([
            'lastPostTime' => $row['time'],
            'lastPosterID' => $row['userID'],
            'lastPoster' => $row['username'],
        ]);
    }

    /**
     * Updates the participant summary of the given conversations.
     *
     * @param int[] $conversationIDs
     */
    public static function updateParticipantSummaries(array $conversationIDs)
    {
        $conversationList = new ConversationList();
        $conversationList->setObjectIDs($conversationIDs);
        $conversationList->readObjects();

        foreach ($conversationList as $conversation) {
            $editor = new self($conversation);
            $editor->updateParticipantSummary();
        }
    }

    /**
     * Updates the participant counts of the given conversations.
     *
     * @param int[] $conversationIDs
     */
    public static function updateParticipantCounts(array $conversationIDs)
    {
        $conversationList = new ConversationList();
        $conversationList->setObjectIDs($conversationIDs);
        $conversationList->readObjects();

        foreach ($conversationList as $conversation) {
            $editor = new self($conversation);
            $editor->updateParticipantCount();
        }
    }
}
