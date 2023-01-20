<?php

namespace wcf\system\event\listener;

/**
 * Merges user conversations.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class ConversationUserMergeListener extends AbstractUserMergeListener
{
    /**
     * @inheritDoc
     */
    protected $databaseTables = [
        'wcf{WCF_N}_conversation',
        'wcf{WCF_N}_conversation_message',
        [
            'name' => 'wcf{WCF_N}_conversation_label',
            'username' => null,
        ],
        [
            'name' => 'wcf{WCF_N}_conversation_to_user',
            'userID' => 'participantID',
            'ignore' => true,
        ],
    ];
}
