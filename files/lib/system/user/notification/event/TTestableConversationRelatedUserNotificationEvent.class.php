<?php

namespace wcf\system\user\notification\event;

use wcf\command\conversation\CreateConversation;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationBuilder;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\message\ConversationMessageAction;
use wcf\data\user\UserProfile;

/**
 * Provides methods to create conversations and conversation messages for testing
 * user notification events.
 *
 * @author      Matthias Schmidt
 * @copyright   2001-2026 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
trait TTestableConversationRelatedUserNotificationEvent
{
    public static function createTestConversation(UserProfile $conversationAuthor, UserProfile $participant): Conversation
    {
        $builder = ConversationBuilder::forCreate()
            ->setSubject('Test Conversation Subject')
            ->setTime(\TIME_NOW)
            ->setUserID($conversationAuthor->userID)
            ->setUsername($conversationAuthor->username)
            ->setParticipantIDs([$participant->userID]);
        $builder->getMessageBuilder()
            ->setMessage('Test Conversation Message');

        return new CreateConversation($builder)();
    }

    public static function createTestConversationMessage(UserProfile $conversationAuthor, UserProfile $messageAuthor): ConversationMessage
    {
        $conversation = self::createTestConversation($conversationAuthor, $messageAuthor);

        return (new ConversationMessageAction([], 'create', [
            'data' => [
                'conversationID' => $conversation->conversationID,
                'message' => 'Test Conversation Message Message',
                'time' => \TIME_NOW,
                'userID' => $messageAuthor->userID,
                'username' => $messageAuthor->username,
            ],
            'conversation' => $conversation,
        ]))->executeAction()['returnValues'];
    }
}
