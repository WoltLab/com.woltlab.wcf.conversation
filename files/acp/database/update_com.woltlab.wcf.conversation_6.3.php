<?php

/**
 * @author      Marcel Werk
 * @copyright   2001-2026 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */

use wcf\system\database\table\column\NotNullInt10DatabaseTableColumn;
use wcf\system\database\table\PartialDatabaseTable;

return [
    PartialDatabaseTable::create('wcf1_conversation_to_user')
        ->columns([
            NotNullInt10DatabaseTableColumn::create('lastMessageTime')
                ->defaultValue(0),
        ]),
];
