<?php

namespace wcf\page;

use wcf\data\conversation\UserConversationList;
use wcf\system\rssFeed\RssFeed;
use wcf\system\rssFeed\RssFeedItem;
use wcf\system\WCF;

/**
 * Outputs a list of recent conversations as an rss feed.
 *
 * @author      Marcel Werk
 * @copyright   2001-2024 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class ConversationRssFeedPage extends AbstractRssFeedPage
{
    /**
     * @inheritDoc
     */
    public $loginRequired = true;

    protected UserConversationList $conversations;

    #[\Override]
    public function readData()
    {
        parent::readData();

        $this->conversations = new UserConversationList(WCF::getUser()->userID);
        $this->conversations->sqlLimit = 20;
        $this->conversations->sqlOrderBy = 'conversation.lastPostTime DESC';
        $this->conversations->readObjects();
    }

    #[\Override]
    protected function getRssFeed(): RssFeed
    {
        $feed = new RssFeed();
        $channel = $this->getDefaultChannel();
        $channel->title(WCF::getLanguage()->get('wcf.conversation.conversations'));

        if ($this->conversations->valid()) {
            $channel->lastBuildDateFromTimestamp($this->conversations->current()->lastPostTime);
        }
        $feed->channel($channel);

        foreach ($this->conversations as $conversation) {
            $item = new RssFeedItem();
            $item
                ->title($conversation->getTitle())
                ->link($conversation->getLink())
                ->description($conversation->getFirstMessage()->getExcerpt())
                ->pubDateFromTimestamp($conversation->lastPostTime)
                ->creator($conversation->lastPoster)
                ->guid($conversation->getLink())
                ->contentEncoded($conversation->getFirstMessage()->getSimplifiedFormattedMessage())
                ->slashComments($conversation->replies);

            $channel->item($item);
        }

        return $feed;
    }
}
