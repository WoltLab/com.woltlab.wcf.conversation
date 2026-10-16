<?php

namespace wcf\command\conversation;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationBuilder;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * Rebuilds the number of replies and attachments as well as the first and
 * last message of the given conversations. Conversations without any
 * messages are deleted.
 *
 * @author      Marcel Werk
 * @copyright   2001-2026 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.3
 */
final class RebuildConversations
{
    /**
     * @param int[] $conversationIDs
     */
    public function __construct(
        public readonly array $conversationIDs,
    ) {}

    public function __invoke(): void
    {
        if ($this->conversationIDs === []) {
            return;
        }

        $conditionBuilder = new PreparedStatementConditionBuilder();
        $conditionBuilder->add('conversation_message.conversationID IN (?)', [$this->conversationIDs]);
        $sql = "SELECT      conversationID, COUNT(messageID) AS messages, SUM(attachments) AS attachments
                FROM        wcf1_conversation_message conversation_message
                " . $conditionBuilder . "
                GROUP BY    conversationID";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditionBuilder->getParameters());

        $sql = "SELECT      messageID
                FROM        wcf1_conversation_message
                WHERE       conversationID = ?
                ORDER BY    time ASC";
        $firstMessageStatement = WCF::getDB()->prepare($sql, 1);

        $sql = "SELECT      time, userID, username
                FROM        wcf1_conversation_message
                WHERE       conversationID = ?
                ORDER BY    time DESC";
        $lastMessageStatement = WCF::getDB()->prepare($sql, 1);

        $conversationIDs = [];
        while ($row = $statement->fetchArray()) {
            if ($row['messages'] === 0) {
                continue;
            }
            $conversationIDs[] = $row['conversationID'];

            $firstMessageStatement->execute([$row['conversationID']]);
            $firstMessageID = $firstMessageStatement->fetchSingleColumn();

            $lastMessageStatement->execute([$row['conversationID']]);
            $lastMessage = $lastMessageStatement->fetchSingleRow();

            ConversationBuilder::forUpdate(new Conversation(null, ['conversationID' => $row['conversationID']]))
                ->setAttachments((int)$row['attachments'])
                ->setReplies($row['messages'] - 1)
                ->setFirstMessageID($firstMessageID)
                ->setLastPost($lastMessage['time'], $lastMessage['userID'], $lastMessage['username'])
                ->update();
        }

        new DeleteConversations(\array_diff($this->conversationIDs, $conversationIDs))();
    }
}
