<?php

/**
 * Deletes orphaned attachments.
 *
 * @author  Tim Duesterhus
 * @copyright   2001-2022 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package WoltLabSuite\Core
 */

use wcf\data\attachment\AttachmentAction;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\package\SplitNodeException;
use wcf\system\WCF;

$objectType = ObjectTypeCache::getInstance()
    ->getObjectTypeByName('com.woltlab.wcf.attachment.objectType', 'com.woltlab.wcf.conversation.message');

$sql = "SELECT  attachmentID
        FROM    wcf1_attachment
        WHERE   objectTypeID = ?
            AND objectID NOT IN (
                SELECT  messageID
                FROM    wcf1_conversation_message
            )";
$statement = WCF::getDB()->prepare($sql, 100);
$statement->execute([$objectType->objectTypeID]);
$attachmentIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);

if (empty($attachmentIDs)) {
    return;
}

(new AttachmentAction([$attachmentIDs], 'delete'))->executeAction();

// If we reached this location we processed at least one attachment.
// If this was the final attachment the next iteration will abort this
// script early, thus not splitting the node.
throw new SplitNodeException();
