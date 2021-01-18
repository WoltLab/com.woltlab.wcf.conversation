<?php

namespace wcf\data\conversation\message;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of conversation messages.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package WoltLabSuite\Core\Data\Conversation\Message
 *
 * @method  ConversationMessage     current()
 * @method  ConversationMessage[]       getObjects()
 * @method  ConversationMessage|null    search($objectID)
 * @property    ConversationMessage[]       $objects
 */
class ConversationMessageList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = ConversationMessage::class;
}
