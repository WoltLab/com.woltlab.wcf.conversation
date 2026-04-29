<?php

namespace wcf\system\cache\runtime;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationList;

/**
 * Runtime cache implementation for conversations.
 *
 * @author  Matthias Schmidt
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractRuntimeCache<Conversation, ConversationList>
 */
class ConversationRuntimeCache extends AbstractRuntimeCache
{
    /**
     * @inheritDoc
     */
    protected $listClassName = ConversationList::class;
}
