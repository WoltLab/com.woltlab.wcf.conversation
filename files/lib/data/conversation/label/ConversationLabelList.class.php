<?php

namespace wcf\data\conversation\label;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of conversation labels.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package WoltLabSuite\Core\Data\Conversation\Label
 *
 * @method  ConversationLabel       current()
 * @method  ConversationLabel[]     getObjects()
 * @method  ConversationLabel|null      getSingleObject()
 * @method  ConversationLabel|null      search($objectID)
 * @property    ConversationLabel[] $objects
 */
class ConversationLabelList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = ConversationLabel::class;
}
