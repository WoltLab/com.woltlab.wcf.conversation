<?php

namespace wcf\system\user\notification\object;

use wcf\data\conversation\message\ConversationMessage;
use wcf\data\DatabaseObjectDecorator;

/**
 * Notification object for conversations.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @mixin ConversationMessage
 * @extends DatabaseObjectDecorator<ConversationMessage>
 */
class ConversationMessageUserNotificationObject extends DatabaseObjectDecorator implements IUserNotificationObject
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = ConversationMessage::class;

    #[\Override]
    public function getTitle(): string
    {
        return $this->getConversation()->subject;
    }

    #[\Override]
    public function getURL(): string
    {
        return $this->getLink();
    }

    #[\Override]
    public function getAuthorID()
    {
        return $this->userID;
    }
}
