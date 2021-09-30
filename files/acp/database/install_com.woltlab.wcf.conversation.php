<?php

/**
 * @author  Tim Duesterhus
 * @copyright   2001-2021 WoltLab GmbH
 * @license WoltLab License <http://www.woltlab.com/license-agreement.html>
 */

use wcf\system\database\table\column\DefaultFalseBooleanDatabaseTableColumn;
use wcf\system\database\table\column\DefaultTrueBooleanDatabaseTableColumn;
use wcf\system\database\table\column\IntDatabaseTableColumn;
use wcf\system\database\table\column\MediumintDatabaseTableColumn;
use wcf\system\database\table\column\MediumtextDatabaseTableColumn;
use wcf\system\database\table\column\NotNullInt10DatabaseTableColumn;
use wcf\system\database\table\column\NotNullVarchar255DatabaseTableColumn;
use wcf\system\database\table\column\ObjectIdDatabaseTableColumn;
use wcf\system\database\table\column\SmallintDatabaseTableColumn;
use wcf\system\database\table\column\TextDatabaseTableColumn;
use wcf\system\database\table\column\TinyintDatabaseTableColumn;
use wcf\system\database\table\column\VarcharDatabaseTableColumn;
use wcf\system\database\table\DatabaseTable;
use wcf\system\database\table\index\DatabaseTableForeignKey;
use wcf\system\database\table\index\DatabaseTableIndex;
use wcf\system\database\table\index\DatabaseTablePrimaryIndex;
use wcf\system\database\table\PartialDatabaseTable;

return [
    PartialDatabaseTable::create('wcf1_user_group')
        ->columns([
            DefaultFalseBooleanDatabaseTableColumn::create('canBeAddedAsConversationParticipant'),
        ]),
    DatabaseTable::create('wcf1_conversation')
        ->columns([
            ObjectIdDatabaseTableColumn::create('conversationID'),
            NotNullVarchar255DatabaseTableColumn::create('subject')
                ->defaultValue(''),
            NotNullInt10DatabaseTableColumn::create('time')
                ->defaultValue(0),
            IntDatabaseTableColumn::create('firstMessageID')
                ->length(10),
            IntDatabaseTableColumn::create('userID')
                ->length(10),
            NotNullVarchar255DatabaseTableColumn::create('username')
                ->defaultValue(''),
            NotNullInt10DatabaseTableColumn::create('lastPostTime')
                ->defaultValue(0),
            IntDatabaseTableColumn::create('lastPosterID')
                ->length(10),
            NotNullVarchar255DatabaseTableColumn::create('lastPoster')
                ->defaultValue(''),
            MediumintDatabaseTableColumn::create('replies')
                ->notNull()
                ->defaultValue(0),
            SmallintDatabaseTableColumn::create('attachments')
                ->notNull()
                ->defaultValue(0),
            MediumintDatabaseTableColumn::create('participants')
                ->notNull()
                ->defaultValue(0),
            TextDatabaseTableColumn::create('participantSummary'),
            DefaultFalseBooleanDatabaseTableColumn::create('participantCanInvite'),
            DefaultFalseBooleanDatabaseTableColumn::create('isClosed'),
            DefaultFalseBooleanDatabaseTableColumn::create('isDraft'),
            MediumtextDatabaseTableColumn::create('draftData'),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['conversationID']),
            DatabaseTableIndex::create('userID')
                ->columns(['userID', 'isDraft']),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['userID'])
                ->referencedTable('wcf1_user')
                ->referencedColumns(['userID'])
                ->onDelete('SET NULL'),
            DatabaseTableForeignKey::create()
                ->columns(['lastPosterID'])
                ->referencedTable('wcf1_user')
                ->referencedColumns(['userID'])
                ->onDelete('SET NULL'),
        ]),
    DatabaseTable::create('wcf1_conversation_to_user')
        ->columns([
            NotNullInt10DatabaseTableColumn::create('conversationID'),
            IntDatabaseTableColumn::create('participantID')
                ->length(10),
            NotNullVarchar255DatabaseTableColumn::create('username')
                ->defaultValue(''),
            TinyintDatabaseTableColumn::create('hideConversation')
                ->length(1)
                ->notNull()
                ->defaultValue(0),
            DefaultFalseBooleanDatabaseTableColumn::create('isInvisible'),
            NotNullInt10DatabaseTableColumn::create('lastVisitTime')
                ->defaultValue(0),
            NotNullInt10DatabaseTableColumn::create('joinedAt')
                ->defaultValue(0),
            NotNullInt10DatabaseTableColumn::create('leftAt')
                ->defaultValue(0),
            IntDatabaseTableColumn::create('lastMessageID')
                ->length(10),
            DefaultTrueBooleanDatabaseTableColumn::create('leftByOwnChoice'),
        ])
        ->indices([
            DatabaseTableIndex::create('participantID')
                ->columns(['participantID', 'conversationID'])
                ->type(DatabaseTableIndex::UNIQUE_TYPE),
            DatabaseTableIndex::create('participantID_2')
                ->columns(['participantID', 'hideConversation']),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['conversationID'])
                ->referencedTable('wcf1_conversation')
                ->referencedColumns(['conversationID'])
                ->onDelete('CASCADE'),
            DatabaseTableForeignKey::create()
                ->columns(['participantID'])
                ->referencedTable('wcf1_user')
                ->referencedColumns(['userID'])
                ->onDelete('SET NULL'),
        ]),
    DatabaseTable::create('wcf1_conversation_message')
        ->columns([
            ObjectIdDatabaseTableColumn::create('messageID'),
            NotNullInt10DatabaseTableColumn::create('conversationID'),
            IntDatabaseTableColumn::create('userID')
                ->length(10),
            NotNullVarchar255DatabaseTableColumn::create('username')
                ->defaultValue(''),
            MediumtextDatabaseTableColumn::create('message')
                ->notNull(),
            NotNullInt10DatabaseTableColumn::create('time')
                ->defaultValue(0),
            SmallintDatabaseTableColumn::create('attachments')
                ->notNull()
                ->defaultValue(0),
            DefaultFalseBooleanDatabaseTableColumn::create('enableHtml'),
            VarcharDatabaseTableColumn::create('ipAddress')
                ->length(39)
                ->notNull()
                ->defaultValue(''),
            NotNullInt10DatabaseTableColumn::create('lastEditTime')
                ->defaultValue(0),
            MediumintDatabaseTableColumn::create('editCount')
                ->length(7)
                ->notNull()
                ->defaultValue(0),
            DefaultFalseBooleanDatabaseTableColumn::create('hasEmbeddedObjects'),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['messageID']),
            DatabaseTableIndex::create('conversationID')
                ->columns(['conversationID', 'userID']),
            DatabaseTableIndex::create('ipAddress')
                ->columns(['ipAddress']),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['conversationID'])
                ->referencedTable('wcf1_conversation')
                ->referencedColumns(['conversationID'])
                ->onDelete('CASCADE'),
            DatabaseTableForeignKey::create()
                ->columns(['userID'])
                ->referencedTable('wcf1_user')
                ->referencedColumns(['userID'])
                ->onDelete('SET NULL'),
        ]),
    PartialDatabaseTable::create('wcf1_conversation')
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['firstMessageID'])
                ->referencedTable('wcf1_conversation_message')
                ->referencedColumns(['messageID'])
                ->onDelete('SET NULL'),
        ]),
    PartialDatabaseTable::create('wcf1_conversation_to_user')
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['lastMessageID'])
                ->referencedTable('wcf1_conversation_message')
                ->referencedColumns(['messageID'])
                ->onDelete('SET NULL'),
        ]),
    DatabaseTable::create('wcf1_conversation_label')
        ->columns([
            ObjectIdDatabaseTableColumn::create('labelID'),
            NotNullInt10DatabaseTableColumn::create('userID'),
            VarcharDatabaseTableColumn::create('label')
                ->length(80)
                ->notNull()
                ->defaultValue(''),
            NotNullVarchar255DatabaseTableColumn::create('cssClassName')
                ->defaultValue(''),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['labelID']),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['userID'])
                ->referencedTable('wcf1_user')
                ->referencedColumns(['userID'])
                ->onDelete('CASCADE'),
        ]),
    DatabaseTable::create('wcf1_conversation_label_to_object')
        ->columns([
            NotNullInt10DatabaseTableColumn::create('labelID'),
            NotNullInt10DatabaseTableColumn::create('conversationID'),
        ])
        ->indices([
            DatabaseTableIndex::create('labelID')
                ->columns(['labelID', 'conversationID'])
                ->type(DatabaseTableIndex::UNIQUE_TYPE),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['labelID'])
                ->referencedTable('wcf1_conversation_label')
                ->referencedColumns(['labelID'])
                ->onDelete('CASCADE'),
            DatabaseTableForeignKey::create()
                ->columns(['conversationID'])
                ->referencedTable('wcf1_conversation')
                ->referencedColumns(['conversationID'])
                ->onDelete('CASCADE'),
        ]),
];
