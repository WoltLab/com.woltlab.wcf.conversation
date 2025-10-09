<?php

namespace wcf\command\conversation;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationEditor;
use wcf\system\log\modification\ConversationModificationLogHandler;
use wcf\system\user\storage\UserStorageHandler;

/**
 * Command for removing a participant from a conversation.
 *
 * @author Olaf Braun
 * @copyright 2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
final class RemoveConversationParticipant
{
    public function __construct(
        public readonly Conversation $conversation,
        public readonly int $participantID,
    ) {}

    public function __invoke(): void
    {
        $editor = new ConversationEditor($this->conversation);
        $editor->removeParticipant($this->participantID);

        $participant = $this->conversation->getOtherParticipant($this->participantID);
        if ($participant !== null && !$participant->isInvisible) {
            ConversationModificationLogHandler::getInstance()->removeParticipant($this->conversation, $this->participantID);
        }

        UserStorageHandler::getInstance()->reset([$this->participantID], 'unreadConversationCount');
    }
}
