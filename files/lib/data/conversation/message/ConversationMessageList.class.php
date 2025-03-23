<?php

namespace wcf\data\conversation\message;

use wcf\data\DatabaseObjectDecorator;
use wcf\data\DatabaseObjectList;

/**
 * Represents a list of conversation messages.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @template TDatabaseObject of ConversationMessage|DatabaseObjectDecorator<ConversationMessage> = ConversationMessage
 * @extends DatabaseObjectList<TDatabaseObject>
 */
class ConversationMessageList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = ConversationMessage::class;
}
