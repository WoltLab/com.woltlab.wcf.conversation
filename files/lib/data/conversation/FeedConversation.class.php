<?php

namespace wcf\data\conversation;

use wcf\data\DatabaseObjectDecorator;
use wcf\data\IFeedEntry;
use wcf\system\request\LinkHandler;

/**
 * Represents a conversation for RSS feeds.
 *
 * @author  Alexander Ebert
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @method  Conversation    getDecoratedObject()
 * @mixin   Conversation
 */
class FeedConversation extends DatabaseObjectDecorator implements IFeedEntry
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = Conversation::class;

    /**
     * @inheritDoc
     */
    public function getLink(): string
    {
        return LinkHandler::getInstance()->getLink('Conversation', [
            'object' => $this->getDecoratedObject(),
            'encodeTitle' => true,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return $this->getDecoratedObject()->getTitle();
    }

    /**
     * @inheritDoc
     */
    public function getFormattedMessage(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getExcerpt($maxLength = 255): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getUserID()
    {
        return $this->getDecoratedObject()->lastPosterID;
    }

    /**
     * @inheritDoc
     */
    public function getUsername(): string
    {
        return $this->getDecoratedObject()->lastPoster;
    }

    /**
     * @inheritDoc
     */
    public function getTime()
    {
        return $this->getDecoratedObject()->lastPostTime;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->getFormattedMessage();
    }

    /**
     * @inheritDoc
     */
    public function getComments()
    {
        return $this->replies;
    }

    /**
     * @inheritDoc
     */
    public function getCategories()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function isVisible()
    {
        return $this->canRead();
    }
}
