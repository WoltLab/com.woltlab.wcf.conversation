<?php

namespace wcf\command\conversation;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationAction;
use wcf\system\log\modification\ConversationModificationLogHandler;

/**
 * Command to add participants to a conversation.
 *
 * @author      Olaf Braun
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 */
final class AddConversationParticipant
{
    public function __construct(
        public readonly Conversation $conversation,
        /**
         * @var int[]
         */
        public readonly array $participants,
        /**
         * @var 'new'|'all'
         */
        public readonly ?string $messageVisibility
    ) {}

    public function __invoke(): void
    {
        if ($this->participants === []) {
            return;
        }

        if ($this->conversation->isDraft) {
            $draftData = \unserialize($this->conversation->draftData);
            $draftData['participants'] = \array_merge($draftData['participants'], $this->participants);
            $data = ['data' => ['draftData' => \serialize($draftData)]];
        } else {
            $data = [
                'participants' => $this->participants,
                'visibility' => $this->messageVisibility,
            ];
        }

        (new ConversationAction([$this->conversation], 'update', $data))->executeAction();

        ConversationModificationLogHandler::getInstance()->addParticipants($this->conversation, $this->participants);
    }
}
