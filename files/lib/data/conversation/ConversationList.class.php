<?php

namespace wcf\data\conversation;

use wcf\data\DatabaseObjectDecorator;
use wcf\data\DatabaseObjectList;

/**
 * Represents a list of conversations.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @template TDatabaseObject of Conversation|DatabaseObjectDecorator<Conversation> = Conversation
 * @extends DatabaseObjectList<TDatabaseObject>
 */
class ConversationList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = Conversation::class;
}
