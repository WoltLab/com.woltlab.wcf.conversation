<?php

namespace wcf\command\conversation;

use wcf\data\conversation\Conversation;
use wcf\system\WCF;

/**
 * Hides the given conversation for the active user.
 *
 * @author      Olaf Braun
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 */
final class HideConversation
{
    public function __construct(
        public readonly Conversation $conversation,
    ) {}

    public function __invoke(): void
    {
        $sql = "UPDATE  wcf1_conversation_to_user
                SET     hideConversation = ?
                WHERE   conversationID = ?
                    AND participantID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([
            Conversation::STATE_HIDDEN,
            $this->conversation->conversationID,
            WCF::getUser()->userID,
        ]);
    }
}
