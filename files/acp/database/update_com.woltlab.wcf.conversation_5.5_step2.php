<?php

/**
 * @author  Tim Duesterhus
 * @copyright   2001-2021 WoltLab GmbH
 * @license WoltLab License <http://www.woltlab.com/license-agreement.html>
 */

use wcf\system\database\table\index\DatabaseTableIndex;
use wcf\system\database\table\index\DatabaseTablePrimaryIndex;
use wcf\system\database\table\PartialDatabaseTable;

// 1) Generate a blueprint to fill in the generated index names.

$blueprint = [
    PartialDatabaseTable::create('wcf1_conversation')
        ->indices([
            // DatabaseTablePrimaryIndex::create()
            //    ->columns(['conversationID']),
            DatabaseTableIndex::create()
                ->columns(['userID', 'isDraft']),
        ]),
    PartialDatabaseTable::create('wcf1_conversation_to_user')
        ->indices([
            DatabaseTableIndex::create()
                ->columns(['participantID', 'conversationID'])
                ->type(DatabaseTableIndex::UNIQUE_TYPE),
            // DatabaseTableIndex::create()
            //    ->columns(['participantID', 'hideConversation']),
        ]),
    PartialDatabaseTable::create('wcf1_conversation_message')
        ->indices([
            // DatabaseTablePrimaryIndex::create()
            //    ->columns(['messageID']),
            DatabaseTableIndex::create()
                ->columns(['conversationID', 'userID']),
            DatabaseTableIndex::create()
                ->columns(['ipAddress']),
        ]),
    PartialDatabaseTable::create('wcf1_conversation_label')
        ->indices([
            // DatabaseTablePrimaryIndex::create()
            //    ->columns(['labelID']),
        ]),
    PartialDatabaseTable::create('wcf1_conversation_label_to_object')
        ->indices([
            DatabaseTableIndex::create()
                ->columns(['labelID', 'conversationID'])
                ->type(DatabaseTableIndex::UNIQUE_TYPE),
        ]),
];

// 2) Use this blueprint to recreate the index objects with ->generatedName() set to false.
// Simply dropping the indices with ->generatedName() set to true does not work, because it will
// also remove named indices as the fact that a name was generated does not persist to the database.

$data = [];
foreach ($blueprint as $blueprintTable) {
    $data[] = PartialDatabaseTable::create($blueprintTable->getName())
        ->indices(\array_map(static function ($index) {
            \assert($index instanceof DatabaseTableIndex);

            return DatabaseTableIndex::create($index->getName())
                ->columns($index->getColumns())
                ->type($index->getType())
                ->drop();
        }, $blueprintTable->getIndices()));
}

return $data;
