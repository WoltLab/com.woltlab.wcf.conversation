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
 */
class ConversationListPageHandler extends AbstractMenuPageHandler
{
    #[\Override]
    public function getOutstandingItemCount(?int $objectID = null)
    {
        return ConversationHandler::getInstance()->getUnreadConversationCount();
    }

    #[\Override]
    public function isVisible(?int $objectID = null)
    {
        return !WCF::getUser()->isGuest();
    }
}
