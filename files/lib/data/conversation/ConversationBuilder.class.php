<?php

namespace wcf\data\conversation;

use wcf\command\conversation\InsertConversationParticipants;
use wcf\data\conversation\message\ConversationMessageBuilder;
use wcf\data\DatabaseObjectBuilder;

/**
 * Builder for creating, updating and deleting conversations.
 *
 * The first message of a conversation is created or updated through the
 * builder returned by `getMessageBuilder()`.
 *
 * Participants are stored in `draftData` as long as the conversation is a
 * draft. Once the conversation is sent, the participants and the author are
 * added to the conversation.
 *
 * @author      Marcel Werk
 * @copyright   2001-2026 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.3
 *
 * @extends DatabaseObjectBuilder<Conversation>
 */
final class ConversationBuilder extends DatabaseObjectBuilder
{
    public private(set) ConversationMessageBuilder $messageBuilder;

    /**
     * @var int[]
     */
    public private(set) array $participantIDs;

    /**
     * @var int[]
     */
    public private(set) array $invisibleParticipantIDs;

    /**
     * @var 'all'|'new'
     */
    public private(set) string $messageVisibility = 'all';

    public function setSubject(string $subject): static
    {
        $this->properties['subject'] = $subject;

        return $this;
    }

    public function setTime(int $time): static
    {
        $this->properties['time'] = $time;

        return $this;
    }

    public function setFirstMessageID(?int $firstMessageID): static
    {
        $this->properties['firstMessageID'] = $firstMessageID;

        return $this;
    }

    public function setUserID(?int $userID): static
    {
        $this->properties['userID'] = $userID;

        return $this;
    }

    public function setUsername(string $username): static
    {
        $this->properties['username'] = $username;

        return $this;
    }

    /**
     * Sets the time and author of the last message of the conversation.
     */
    public function setLastPost(int $lastPostTime, ?int $lastPosterID, string $lastPoster): static
    {
        $this->properties['lastPostTime'] = $lastPostTime;
        $this->properties['lastPosterID'] = $lastPosterID;
        $this->properties['lastPoster'] = $lastPoster;

        return $this;
    }

    public function setLastPostTime(int $lastPostTime): static
    {
        $this->properties['lastPostTime'] = $lastPostTime;

        return $this;
    }

    public function setReplies(int $replies): static
    {
        $this->properties['replies'] = $replies;

        return $this;
    }

    public function incrementReplies(int $replies): static
    {
        $this->incrementProperties['replies'] = $replies;

        return $this;
    }

    public function setAttachments(int $attachments): static
    {
        $this->properties['attachments'] = $attachments;

        return $this;
    }

    public function incrementAttachments(int $attachments): static
    {
        $this->incrementProperties['attachments'] = $attachments;

        return $this;
    }

    public function setParticipants(int $participants): static
    {
        $this->properties['participants'] = $participants;

        return $this;
    }

    public function incrementParticipants(int $participants): static
    {
        $this->incrementProperties['participants'] = $participants;

        return $this;
    }

    public function setParticipantCanInvite(bool $participantCanInvite): static
    {
        $this->properties['participantCanInvite'] = $participantCanInvite ? 1 : 0;

        return $this;
    }

    public function setIsClosed(bool $isClosed): static
    {
        $this->properties['isClosed'] = $isClosed ? 1 : 0;

        return $this;
    }

    public function setIsDraft(bool $isDraft): static
    {
        $this->properties['isDraft'] = $isDraft ? 1 : 0;

        return $this;
    }

    /**
     * Sets the participants of the conversation. For a draft, these replace
     * the stored participants. For a sent conversation, these are added to the
     * existing participants.
     *
     * @param int[] $participantIDs
     */
    public function setParticipantIDs(array $participantIDs): static
    {
        $this->participantIDs = $participantIDs;

        return $this;
    }

    /**
     * Sets the invisible participants of the conversation, see `setParticipantIDs()`.
     *
     * @param int[] $invisibleParticipantIDs
     */
    public function setInvisibleParticipantIDs(array $invisibleParticipantIDs): static
    {
        $this->invisibleParticipantIDs = $invisibleParticipantIDs;

        return $this;
    }

    /**
     * Sets whether participants added to an existing conversation can see all
     * messages or only the messages written after they have joined.
     *
     * @param 'all'|'new' $messageVisibility
     */
    public function setMessageVisibility(string $messageVisibility): static
    {
        $this->messageVisibility = $messageVisibility;

        return $this;
    }

    /**
     * Returns the builder for the first message of the conversation, creating
     * it on demand.
     */
    public function getMessageBuilder(): ConversationMessageBuilder
    {
        if (!isset($this->messageBuilder)) {
            $firstMessage = $this->existingObject?->getFirstMessage();

            $this->messageBuilder = $firstMessage !== null
                ? ConversationMessageBuilder::forUpdate($firstMessage)
                : ConversationMessageBuilder::forCreate();
        }

        return $this->messageBuilder;
    }

    #[\Override]
    protected function afterCreate(): void
    {
        if (isset($this->messageBuilder)) {
            $this->createFirstMessage($this->getObject());
        }

        if ($this->getObject()->isDraft === 1) {
            $this->saveDraftParticipants($this->getObject());
        } else {
            $this->insertParticipants($this->getObject(), true);
        }

        $this->reloadObject();
    }

    #[\Override]
    protected function afterUpdate(): void
    {
        if (isset($this->messageBuilder)) {
            if ($this->messageBuilder->isUpdate()) {
                $this->messageBuilder->update();
            } else {
                $this->createFirstMessage($this->getObject());
            }
        }

        if ($this->getObject()->isDraft === 1) {
            if (isset($this->participantIDs) || isset($this->invisibleParticipantIDs)) {
                $this->saveDraftParticipants($this->getObject());
            }
        } else {
            $isSent = $this->getExistingObject()->isDraft === 1;
            $this->insertParticipants($this->getObject(), $isSent);

            if ($isSent) {
                ConversationBuilder::forUpdate($this->getObject())
                    ->setDraftData([])
                    ->update();
            }
        }

        $this->reloadObject();
    }

    /**
     * Reloads the object to reflect the changes made by the follow-up writes
     * in `afterCreate()` and `afterUpdate()`.
     */
    private function reloadObject(): void
    {
        $this->object = new Conversation($this->getObject()->conversationID);
    }

    private function createFirstMessage(Conversation $conversation): void
    {
        $message = $this->messageBuilder
            ->setConversationID($conversation->conversationID)
            ->setUserID($conversation->userID)
            ->setUsername($conversation->username)
            ->setTime($conversation->time)
            ->create();

        ConversationBuilder::forUpdate($conversation)
            ->setFirstMessageID($message->messageID)
            ->setAttachments($message->attachments)
            ->update();
    }

    private function saveDraftParticipants(Conversation $conversation): void
    {
        $draftData = @\unserialize($conversation->draftData ?? '');
        if (!\is_array($draftData)) {
            $draftData = [];
        }

        ConversationBuilder::forUpdate($conversation)
            ->setDraftData([
                'participants' => $this->participantIDs ?? $draftData['participants'] ?? [],
                'invisibleParticipants' => $this->invisibleParticipantIDs ?? $draftData['invisibleParticipants'] ?? [],
            ])
            ->update();
    }

    /**
     * Adds the participants to the conversation. The author is added when the
     * conversation is sent.
     */
    private function insertParticipants(Conversation $conversation, bool $includeAuthor): void
    {
        $participantIDs = $this->participantIDs ?? [];
        if ($includeAuthor && $conversation->userID !== null) {
            $participantIDs[] = $conversation->userID;
        }
        $invisibleParticipantIDs = $this->invisibleParticipantIDs ?? [];

        if ($participantIDs === [] && $invisibleParticipantIDs === []) {
            return;
        }

        new InsertConversationParticipants(
            $conversation,
            $participantIDs,
            $invisibleParticipantIDs,
            $this->messageVisibility
        )();
    }

    /**
     * @param array{}|array{participants: int[], invisibleParticipants: int[]} $draftData
     */
    private function setDraftData(array $draftData): static
    {
        $this->properties['draftData'] = \serialize($draftData);

        return $this;
    }

    #[\Override]
    protected function getRequiredProperties(): array
    {
        return ['subject', 'time', 'userID', 'username'];
    }
}
