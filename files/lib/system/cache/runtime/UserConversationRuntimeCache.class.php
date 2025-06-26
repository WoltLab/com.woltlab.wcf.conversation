<?php

namespace wcf\system\cache\runtime;

use wcf\data\conversation\UserConversationList;
use wcf\data\conversation\ViewableConversation;
use wcf\system\WCF;

/**
 * Runtime cache implementation for conversation fetched using UserConversationList.
 *
 * @author  Matthias Schmidt
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since   3.0
 *
 * @extends AbstractRuntimeCache<ViewableConversation, UserConversationList>
 */
class UserConversationRuntimeCache extends AbstractRuntimeCache
{
    /**
     * @inheritDoc
     */
    protected $listClassName = UserConversationList::class;

    /**
     * @inheritDoc
     */
    protected function getObjectList()
    {
        return new UserConversationList(WCF::getUser()->userID);
    }
}
