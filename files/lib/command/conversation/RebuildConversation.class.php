<?php

namespace wcf\command\conversation;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationEditor;
use wcf\system\WCF;

/**
 * Rebuilds the conversation data.
 *
 * @author Olaf Braun
 * @copyright 2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.3
 */
final class RebuildConversation
{
    public function __construct(
        private readonly Conversation $conversation
    ) {}

    public function __invoke(): void
    {
        ['messages' => $messages, 'attachments' => $attachments] = $this->getConversationStats($this->conversation->conversationID);

        $editor = new ConversationEditor($this->conversation);
        if ($messages === 0) {
            $editor->delete();

            return;
        }

        $editor->update([
            'attachments' => $attachments,
            'replies' => $messages - 1,
        ]);

        $editor->updateFirstMessage();
        $editor->updateLastMessage();
    }

    /**
     * @return array{attachments: int, messages: int}
     */
    private function getConversationStats(int $conversationID): array
    {
        $sql = "SELECT      COUNT(messageID) AS messages,
                            COALESCE(SUM(attachments), 0) AS attachments
                FROM        wcf1_conversation_message
                WHERE       conversationID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$conversationID]);
        $row = $statement->fetchArray();

        return [
            'messages' => $row['messages'],
            'attachments' => $row['attachments'],
        ];
    }
}
