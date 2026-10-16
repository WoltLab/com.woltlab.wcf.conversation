<?php

namespace wcf\command\conversation;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationBuilder;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\participant\ConversationParticipant;
use wcf\data\conversation\participant\ConversationParticipantBuilder;
use wcf\system\search\SearchIndexManager;
use wcf\system\user\notification\object\ConversationUserNotificationObject;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\user\storage\UserStorageHandler;
use wcf\util\UserUtil;

/**
 * Creates a new conversation including its first message and participants.
 *
 * @author      Marcel Werk
 * @copyright   2001-2026 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.3
 */
final class CreateConversation
{
    public function __construct(
        private readonly ConversationBuilder $builder,
    ) {}

    public function __invoke(): Conversation
    {
        $properties = $this->builder->properties;
        $this->builder->setLastPost(
            (int)($properties['time'] ?? 0),
            isset($properties['userID']) ? (int)$properties['userID'] : null,
            (string)($properties['username'] ?? '')
        );

        $messageBuilder = $this->builder->getMessageBuilder();
        if ($messageBuilder->getMessage() === null) {
            throw new \BadMethodCallException("Missing value for the first message of the conversation.");
        }
        if (!isset($messageBuilder->properties['enableHtml'])) {
            $messageBuilder->setEnableHtml(true);
        }
        if (\LOG_IP_ADDRESS === 1 && !isset($messageBuilder->properties['ipAddress'])) {
            $messageBuilder->setIpAddress(UserUtil::getIpAddress());
        }

        $conversation = $this->builder->create();
        // Reload the object so that `firstMessageID` is set.
        $conversation = new Conversation($conversation->conversationID);

        if ($conversation->isDraft === 0) {
            if ($conversation->userID !== null) {
                $this->markAsReadForAuthor($conversation);
            }

            $participantIDs = $conversation->getParticipantIDs();
            UserStorageHandler::getInstance()->reset($participantIDs, 'conversationCount');
            UserStorageHandler::getInstance()->reset($participantIDs, 'unreadConversationCount');
        } elseif ($conversation->userID !== null) {
            UserStorageHandler::getInstance()->reset([$conversation->userID], 'conversationCount');
        }

        $this->updateSearchIndex($conversation);

        if ($conversation->isDraft === 0) {
            UserNotificationHandler::getInstance()->fireEvent(
                'conversation',
                'com.woltlab.wcf.conversation.notification',
                new ConversationUserNotificationObject($conversation),
                \array_merge($this->builder->participantIDs ?? [], $this->builder->invisibleParticipantIDs ?? [])
            );
        }

        return $conversation;
    }

    private function markAsReadForAuthor(Conversation $conversation): void
    {
        \assert($conversation->userID !== null);

        $participant = ConversationParticipant::getParticipant($conversation->conversationID, $conversation->userID);
        if ($participant === null) {
            return;
        }

        ConversationParticipantBuilder::forUpdate($participant)
            ->setLastVisitTime($conversation->time)
            ->update();
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
