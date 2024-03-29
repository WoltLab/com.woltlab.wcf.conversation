<?php

namespace wcf\system\page\handler;

use wcf\system\conversation\ConversationHandler;
use wcf\system\WCF;

/**
 * Page handler implementation for the conversation list.
 *
 * @author  Matthias Schmidt
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since   3.0
 */
class ConversationListPageHandler extends AbstractMenuPageHandler
{
    /**
     * @inheritDoc
     */
    public function getOutstandingItemCount($objectID = null)
    {
        return ConversationHandler::getInstance()->getUnreadConversationCount();
    }

    /**
     * @inheritDoc
     */
    public function isVisible($objectID = null)
    {
        return WCF::getUser()->userID != 0;
    }
}
