<?php

namespace wcf\command\conversation;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationEditor;
use wcf\system\search\SearchIndexManager;

/**
 * Sets the subject of a conversation.
 *
 * @author      Olaf Braun
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 */
final class SetConversationSubject
{
    public function __construct(
        public readonly Conversation $conversation,
        public readonly string $subject,
    ) {}

    public function __invoke(): void
    {
        $editor = new ConversationEditor($this->conversation);
        $editor->update([
            'subject' => $this->subject,
        ]);

        $message = $this->conversation->getFirstMessage();

        SearchIndexManager::getInstance()->set(
            'com.woltlab.wcf.conversation.message',
            $message->messageID,
            $message->message,
            $this->subject,
            $message->time,
            $message->userID,
            $message->username
        );
    }
}
