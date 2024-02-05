<?php

namespace wcf\page;

use wcf\data\conversation\FeedConversationList;
use wcf\system\WCF;

/**
 * Shows most recent conversations.
 *
 * @author  Alexander Ebert
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @deprecated 6.1 use `ConversationRssFeedPage` instead
 */
class ConversationFeedPage extends AbstractFeedPage
{
    /**
     * @inheritDoc
     */
    public $loginRequired = true;

    /**
     * @inheritDoc
     */
    public function readParameters()
    {
        parent::readParameters();

        $this->redirectToNewPage(ConversationRssFeedPage::class);
    }

    /**
     * @inheritDoc
     */
    public function readData()
    {
        parent::readData();

        $this->items = new FeedConversationList();
        $this->items->getConditionBuilder()->add('conversation_to_user.participantID = ?', [WCF::getUser()->userID]);
        $this->items->getConditionBuilder()->add('conversation_to_user.hideConversation = ?', [0]);
        $this->items->sqlConditionJoins = "
            LEFT JOIN   wcf" . WCF_N . "_conversation conversation
            ON          conversation.conversationID = conversation_to_user.conversationID";
        $this->items->sqlLimit = 20;
        $this->items->readObjects();

        $this->title = WCF::getLanguage()->get('wcf.conversation.conversations');
    }
}
