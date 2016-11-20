<?php
use wcf\system\WCF;

// MySQL drops a column by creating a new table in the
// background, copying over all data except from the
// deleted column and uses this table afterwards.
// 
// Using a single `ALTER TABLE` to drop multiple columns
// results in the same runtime, because copying the table
// is what actually takes ages.
$statement = WCF::getDB()->prepareStatement("ALTER TABLE wcf1_conversation_message DROP COLUMN enableSmilies, DROP COLUMN enableBBCodes, DROP COLUMN showSignature;");
$statement->execute();
