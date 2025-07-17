<?php

namespace wcf\data\conversation\participant;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit conversation participants.
 *
 * @author      Marcel Werk
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 *
 * @mixin ConversationParticipant
 * @extends DatabaseObjectEditor<ConversationParticipant>
 */
class ConversationParticipantEditor extends DatabaseObjectEditor
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = ConversationParticipant::class;
}
