<?php

namespace wcf\data\conversation;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of conversations.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @method  Conversation        current()
 * @method  Conversation[]      getObjects()
 * @method  Conversation|null   getSingleObject()
 * @method  Conversation|null   search($objectID)
 * @property    Conversation[] $objects
 */
class ConversationList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = Conversation::class;
}
