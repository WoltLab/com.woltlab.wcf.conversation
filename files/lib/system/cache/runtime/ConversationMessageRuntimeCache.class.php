<?php

namespace wcf\system\cache\runtime;

use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\message\ConversationMessageList;

/**
 * Runtime cache implementation for conversation messages.
 *
 * @author      Matthias Schmidt, Marcel Werk
 * @copyright   2001-2024 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.1
 *
 * @method  ConversationMessage[]      getCachedObjects()
 * @method  ConversationMessage        getObject($objectID)
 * @method  ConversationMessage[]      getObjects(array $objectIDs)
 */
class ConversationMessageRuntimeCache extends AbstractRuntimeCache
{
    /**
     * @inheritDoc
     */
    protected $listClassName = ConversationMessageList::class;
}
