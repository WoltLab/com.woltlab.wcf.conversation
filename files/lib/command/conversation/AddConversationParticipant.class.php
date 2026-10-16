<?php

namespace wcf\command\conversation;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationBuilder;
use wcf\system\log\modification\ConversationModificationLogHandler;

/**
 * Adds participants to a conversation.
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

        $builder = ConversationBuilder::forUpdate($this->conversation);
        if ($this->conversation->isDraft === 1) {
            $draftData = \unserialize($this->conversation->draftData);
            $builder->setParticipantIDs(\array_merge($draftData['participants'], $this->participants));
        } else {
            $builder
                ->setParticipantIDs($this->participants)
                ->setMessageVisibility($this->messageVisibility ?? 'all');
        }

        new UpdateConversation($builder)();

        ConversationModificationLogHandler::getInstance()->addParticipants($this->conversation, $this->participants);
    }
}
