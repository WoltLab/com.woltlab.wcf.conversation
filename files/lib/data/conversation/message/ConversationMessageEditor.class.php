<?php

namespace wcf\data\conversation\message;

use wcf\data\DatabaseObjectEditor;

/**
 * Extends the message object with functions to create, update and delete messages.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @mixin ConversationMessage
 * @extends DatabaseObjectEditor<ConversationMessage>
 */
class ConversationMessageEditor extends DatabaseObjectEditor
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = ConversationMessage::class;
}
