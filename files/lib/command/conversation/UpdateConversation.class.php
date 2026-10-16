<?php

namespace wcf\command\conversation;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationBuilder;
use wcf\data\conversation\message\ConversationMessage;
use wcf\system\search\SearchIndexManager;
use wcf\system\user\notification\object\ConversationUserNotificationObject;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\user\storage\UserStorageHandler;

/**
 * Updates a conversation and notifies newly added participants.
 *
 * @author      Marcel Werk
 * @copyright   2001-2026 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.3
 */
final class UpdateConversation
{
    public function __construct(
        private readonly ConversationBuilder $builder,
    ) {}

    public function __invoke(): Conversation
    {
        $oldConversation = $this->builder->getObject();
        $addedParticipantIDs = \array_merge(
            $this->builder->participantIDs ?? [],
            $this->builder->invisibleParticipantIDs ?? []
        );
        $existingParticipantIDs = $addedParticipantIDs !== [] ? $oldConversation->getParticipantIDs() : [];

        $conversation = $this->builder->update();

        if (isset($this->builder->messageBuilder) || isset($this->builder->properties['subject'])) {
            $this->updateSearchIndex($conversation);
        }

        if ($conversation->isDraft === 0) {
            $this->notifyNewParticipants($conversation, \array_diff($addedParticipantIDs, $existingParticipantIDs));
        }

        if ($oldConversation->isDraft === 1 && $conversation->isDraft === 0) {
            $participantIDs = $conversation->getParticipantIDs();
            UserStorageHandler::getInstance()->reset($participantIDs, 'unreadConversationCount');
            UserStorageHandler::getInstance()->reset($participantIDs, 'conversationCount');
        }

        return $conversation;
    }

    /**
     * @param int[] $newParticipantIDs
     */
    private function notifyNewParticipants(Conversation $conversation, array $newParticipantIDs): void
    {
        if ($newParticipantIDs === []) {
            return;
        }

        UserStorageHandler::getInstance()->reset($newParticipantIDs, 'unreadConversationCount');
        UserStorageHandler::getInstance()->reset($newParticipantIDs, 'conversationCount');

        UserNotificationHandler::getInstance()->fireEvent(
            'conversation',
            'com.woltlab.wcf.conversation.notification',
            new ConversationUserNotificationObject($conversation),
            $newParticipantIDs
        );
    }

    private function updateSearchIndex(Conversation $conversation): void
    {
        if ($conversation->firstMessageID === null) {
            return;
        }

        $message = new ConversationMessage($conversation->firstMessageID);

        SearchIndexManager::getInstance()->set(
            'com.woltlab.wcf.conversation.message',
            $message->messageID,
            $message->message,
            $conversation->subject,
            $message->time,
            $message->userID,
            $message->username
        );
    }
}
