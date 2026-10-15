<?php

/**
 * Stores the time of the last accessible message for participants that have been
 * removed from a conversation.
 *
 * @author      Marcel Werk
 * @copyright   2001-2026 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */

use wcf\system\WCF;

$sql = "UPDATE  wcf1_conversation_to_user conversation_to_user
        JOIN    wcf1_conversation_message conversation_message
        ON      conversation_message.messageID = conversation_to_user.lastMessageID
        SET     conversation_to_user.lastMessageTime = conversation_message.time
        WHERE   conversation_to_user.leftAt <> ?";
$statement = WCF::getDB()->prepare($sql);
$statement->execute([0]);
