<?php

namespace wcf\system\user\notification\event;

use wcf\data\user\UserProfile;
use wcf\system\email\Email;
use wcf\system\user\notification\object\ConversationMessageUserNotificationObject;

/**
 * User notification event for conversation messages.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @method  ConversationMessageUserNotificationObject   getUserNotificationObject()
 */
class ConversationMessageUserNotificationEvent extends AbstractUserNotificationEvent implements
    ITestableUserNotificationEvent
{
    use TTestableConversationRelatedUserNotificationEvent;
    use TTestableUserNotificationEvent;

    /**
     * @inheritDoc
     */
    protected $stackable = true;

    #[\Override]
    public function getTitle(): string
    {
        $count = \count($this->getAuthors());
        if ($count > 1) {
            return $this->getLanguage()->getDynamicVariable(
                'wcf.user.notification.conversation.message.title.stacked',
                ['count' => $count]
            );
        }

        return $this->getLanguage()->get('wcf.user.notification.conversation.message.title');
    }

    #[\Override]
    public function getMessage(): string
    {
        $authors = \array_values($this->getAuthors());
        $count = \count($authors);

        if ($count > 1) {
            return $this->getLanguage()->getDynamicVariable(
                'wcf.user.notification.conversation.message.message.stacked',
                [
                    'author' => $this->author,
                    'authors' => $authors,
                    'count' => $count,
                    'message' => $this->userNotificationObject,
                    'others' => $count - 1,
                ]
            );
        }

        return $this->getLanguage()->getDynamicVariable('wcf.user.notification.conversation.message.message', [
            'author' => $this->author,
            'message' => $this->userNotificationObject,
        ]);
    }

    #[\Override]
    public function getEmailMessage(string $notificationType = 'instant'): array
    {
        $messageID = '<com.woltlab.wcf.conversation.notification/' . $this->getUserNotificationObject()->getConversation()->conversationID . '@' . Email::getHost() . '>';

        return [
            'template' => 'email_notification_conversationMessage',
            'application' => 'wcf',
            'in-reply-to' => [$messageID],
            'references' => [$messageID],
            'variables' => [
                'author' => $this->author,
                'message' => $this->userNotificationObject,
                'conversation' => $this->getUserNotificationObject()->getConversation(),
            ],
        ];
    }

    #[\Override]
    public function getEmailTitle(): string
    {
        if (\count($this->getAuthors()) > 1) {
            return parent::getEmailTitle();
        }

        return $this->getLanguage()->getDynamicVariable('wcf.user.notification.conversation.message.mail.title', [
            'author' => $this->author,
            'message' => $this->userNotificationObject,
            'conversation' => $this->getUserNotificationObject()->getConversation(),
        ]);
    }

    #[\Override]
    public function getLink(): string
    {
        return $this->getUserNotificationObject()->getLink();
    }

    #[\Override]
    public function getEventHash(): string
    {
        return \sha1($this->eventID . '-' . $this->getUserNotificationObject()->conversationID);
    }

    #[\Override]
    public function checkAccess(): bool
    {
        return $this->getUserNotificationObject()->getConversation()->canRead();
    }

    #[\Override]
    public static function getTestObjects(UserProfile $recipient, UserProfile $author)
    {
        return [
            new ConversationMessageUserNotificationObject(self::createTestConversationMessage($recipient, $author)),
        ];
    }
}
