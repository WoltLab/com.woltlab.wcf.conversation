<?php

namespace wcf\data\conversation\participant;

use wcf\data\conversation\Conversation;
use wcf\data\DatabaseObjectBuilder;

/**
 * Builder for creating, updating and deleting conversation participants.
 *
 * @author      Marcel Werk
 * @copyright   2001-2026 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.3
 *
 * @extends DatabaseObjectBuilder<ConversationParticipant>
 */
final class ConversationParticipantBuilder extends DatabaseObjectBuilder
{
    public function setConversationID(int $conversationID): static
    {
        $this->properties['conversationID'] = $conversationID;

        return $this;
    }

    public function setParticipantID(?int $participantID): static
    {
        $this->properties['participantID'] = $participantID;

        return $this;
    }

    public function setUsername(string $username): static
    {
        $this->properties['username'] = $username;

        return $this;
    }

    /**
     * @param Conversation::STATE_* $hideConversation
     */
    public function setHideConversation(int $hideConversation): static
    {
        $this->properties['hideConversation'] = $hideConversation;

        return $this;
    }

    public function setIsInvisible(bool $isInvisible): static
    {
        $this->properties['isInvisible'] = $isInvisible ? 1 : 0;

        return $this;
    }

    public function setLastVisitTime(int $lastVisitTime): static
    {
        $this->properties['lastVisitTime'] = $lastVisitTime;

        return $this;
    }

    public function setJoinedAt(int $joinedAt): static
    {
        $this->properties['joinedAt'] = $joinedAt;

        return $this;
    }

    public function setLeftAt(int $leftAt): static
    {
        $this->properties['leftAt'] = $leftAt;

        return $this;
    }

    public function setLastMessageID(?int $lastMessageID): static
    {
        $this->properties['lastMessageID'] = $lastMessageID;

        return $this;
    }

    public function setLastMessageTime(int $lastMessageTime): static
    {
        $this->properties['lastMessageTime'] = $lastMessageTime;

        return $this;
    }

    public function setLeftByOwnChoice(bool $leftByOwnChoice): static
    {
        $this->properties['leftByOwnChoice'] = $leftByOwnChoice ? 1 : 0;

        return $this;
    }

    #[\Override]
    protected function getRequiredProperties(): array
    {
        return ['conversationID'];
    }
}
