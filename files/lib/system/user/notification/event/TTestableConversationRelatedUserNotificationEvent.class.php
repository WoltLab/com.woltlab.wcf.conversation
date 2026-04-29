<?php

namespace wcf\system\user\notification\event;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationAction;
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
        return (new ConversationAction([], 'create', [
            'data' => [
                'subject' => 'Test Conversation Subject',
                'time' => \TIME_NOW,
                'userID' => $conversationAuthor->userID,
                'username' => $conversationAuthor->username,
            ],
            'messageData' => [
                'message' => 'Test Conversation Message',
            ],
            'participants' => [$participant->userID],
        ]))->executeAction()['returnValues'];
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
