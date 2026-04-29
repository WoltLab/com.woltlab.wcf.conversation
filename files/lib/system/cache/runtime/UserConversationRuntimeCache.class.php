<?php

namespace wcf\system\cache\runtime;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\UserConversationList;
use wcf\system\WCF;

/**
 * Runtime cache implementation for conversation fetched using UserConversationList.
 *
 * @author  Matthias Schmidt
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @deprecated 6.2 Use `ConversationRuntimeCache` instead.
 *
 * @extends AbstractRuntimeCache<Conversation, UserConversationList>
 */
class UserConversationRuntimeCache extends AbstractRuntimeCache
{
    /**
     * @inheritDoc
     */
    protected $listClassName = UserConversationList::class;

    #[\Override]
    protected function getObjectList()
    {
        return new UserConversationList(WCF::getUser()->userID);
    }
}
