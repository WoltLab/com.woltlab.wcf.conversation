<?php

/**
 * @author      Marcel Werk
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */

use wcf\system\database\table\column\ObjectIdDatabaseTableColumn;
use wcf\system\database\table\column\TextDatabaseTableColumn;
use wcf\system\database\table\index\DatabaseTablePrimaryIndex;
use wcf\system\database\table\PartialDatabaseTable;

return [
    PartialDatabaseTable::create('wcf1_conversation')
        ->columns([
            TextDatabaseTableColumn::create('participantSummary')->drop(),
        ]),
    PartialDatabaseTable::create('wcf1_conversation_to_user')
        ->columns([
            ObjectIdDatabaseTableColumn::create('conversationParticipantID'),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['conversationParticipantID']),
        ]),
];
