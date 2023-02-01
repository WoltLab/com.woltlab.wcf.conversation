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

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function getEmailMessage($notificationType = 'instant')
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

    /**
     * @inheritDoc
     * @since   5.2
     */
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

    /**
     * @inheritDoc
     */
    public function getLink(): string
    {
        return $this->getUserNotificationObject()->getLink();
    }

    /**
     * @inheritDoc
     */
    public function getEventHash(): string
    {
        return \sha1($this->eventID . '-' . $this->getUserNotificationObject()->conversationID);
    }

    /**
     * @inheritDoc
     */
    public function checkAccess(): bool
    {
        return $this->getUserNotificationObject()->getConversation()->canRead();
    }

    /**
     * @inheritDoc
     */
    public static function getTestObjects(UserProfile $recipient, UserProfile $author)
    {
        return [
            new ConversationMessageUserNotificationObject(self::createTestConversationMessage($recipient, $author)),
        ];
    }
}
