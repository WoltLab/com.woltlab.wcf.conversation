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
 * @extends AbstractRuntimeCache<ConversationMessage, ConversationMessageList>
 */
class ConversationMessageRuntimeCache extends AbstractRuntimeCache
{
    /**
     * @inheritDoc
     */
    protected $listClassName = ConversationMessageList::class;
}
