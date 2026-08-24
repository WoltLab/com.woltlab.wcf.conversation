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
        $channel->title(\sprintf(
            '%s - %s',
            WCF::getLanguage()->get('wcf.conversation.conversations'),
            WCF::getLanguage()->get(\PAGE_TITLE)
        ));

        if ($this->conversations->valid()) {
            $channel->lastBuildDateFromTimestamp($this->conversations->current()->lastPostTime);
        }
        $feed->channel($channel);

        foreach ($this->conversations as $conversation) {
            $item = new RssFeedItem();
            $item
                ->title($conversation->getTitle())
                ->link($conversation->getLink())
                ->pubDateFromTimestamp($conversation->lastPostTime)
                ->creator($conversation->lastPoster)
                ->guid($conversation->getLink())
                ->slashComments($conversation->replies);

            if ($conversation->canReadFirstMessage()) {
                $item
                    ->description($conversation->getFirstMessage()->getExcerpt())
                    ->contentEncoded($conversation->getFirstMessage()->getSimplifiedFormattedMessage());
            }

            $channel->item($item);
        }

        return $feed;
    }
}
