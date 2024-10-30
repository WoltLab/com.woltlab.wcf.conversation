<?php

namespace wcf\system\event\listener;

/**
 * Updates the stored username during user rename.
 *
 * @author  Alexander Ebert
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class ConversationUserActionRenameListener extends AbstractUserActionRenameListener
{
    /**
     * @inheritDoc
     */
    protected $databaseTables = [
        'wcf1_conversation',
        'wcf1_conversation_message',
        [
            'name' => 'wcf1_conversation',
            'userID' => 'lastPosterID',
            'username' => 'lastPoster',
        ],
        [
            'name' => 'wcf1_conversation_to_user',
            'userID' => 'participantID',
        ],
    ];
}
