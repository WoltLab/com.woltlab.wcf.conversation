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
        'wcf{WCF_N}_conversation',
        'wcf{WCF_N}_conversation_message',
        [
            'name' => 'wcf{WCF_N}_conversation',
            'userID' => 'lastPosterID',
            'username' => 'lastPoster',
        ],
        [
            'name' => 'wcf{WCF_N}_conversation_to_user',
            'userID' => 'participantID',
        ],
    ];
}
