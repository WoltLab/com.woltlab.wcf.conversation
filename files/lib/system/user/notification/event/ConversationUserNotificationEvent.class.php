<?php

namespace wcf\system\user\notification\event;

use wcf\data\user\UserProfile;
use wcf\system\user\notification\object\ConversationUserNotificationObject;

/**
 * User notification event for conversations.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @method  ConversationUserNotificationObject  getUserNotificationObject()
 */
class ConversationUserNotificationEvent extends AbstractUserNotificationEvent implements ITestableUserNotificationEvent
{
    use TTestableConversationRelatedUserNotificationEvent;
    use TTestableUserNotificationEvent;

    #[\Override]
    public function getTitle(): string
    {
        return $this->getLanguage()->get('wcf.user.notification.conversation.title');
    }

    #[\Override]
    public function getMessage(): string
    {
        return $this->getLanguage()->getDynamicVariable('wcf.user.notification.conversation.message', [
            'author' => $this->author,
            'conversation' => $this->userNotificationObject,
        ]);
    }

    #[\Override]
    public function getEmailMessage(string $notificationType = 'instant'): array
    {
        return [
            'message-id' => 'com.woltlab.wcf.conversation.notification/' . $this->getUserNotificationObject()->conversationID,
            'template' => 'email_notification_conversation',
            'application' => 'wcf',
            'variables' => [
                'author' => $this->author,
                'conversation' => $this->userNotificationObject,
            ],
        ];
    }

    #[\Override]
    public function getEmailTitle(): string
    {
        return $this->getLanguage()->getDynamicVariable('wcf.user.notification.conversation.mail.title', [
            'author' => $this->author,
            'conversation' => $this->userNotificationObject,
        ]);
    }

    #[\Override]
    public function getLink(): string
    {
        return $this->getUserNotificationObject()->getLink();
    }

    #[\Override]
    public function checkAccess(): bool
    {
        return $this->getUserNotificationObject()->canRead();
    }

    #[\Override]
    public static function getTestObjects(UserProfile $recipient, UserProfile $author)
    {
        return [new ConversationUserNotificationObject(self::createTestConversation($author, $recipient))];
    }
}
