<?php

namespace wcf\system\user\notification\object;

use wcf\data\conversation\Conversation;
use wcf\data\DatabaseObjectDecorator;

/**
 * Notification object for conversations.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @method  Conversation    getDecoratedObject()
 * @mixin   Conversation
 */
class ConversationUserNotificationObject extends DatabaseObjectDecorator implements IUserNotificationObject
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = Conversation::class;

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return $this->subject;
    }

    /**
     * @inheritDoc
     */
    public function getURL(): string
    {
        return $this->getLink();
    }

    /**
     * @inheritDoc
     */
    public function getAuthorID()
    {
        return $this->userID;
    }
}
