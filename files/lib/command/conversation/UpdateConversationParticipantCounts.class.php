<?php

namespace wcf\command\conversation;

use wcf\data\conversation\Conversation;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * Recalculates the number of participants of the given conversations.
 *
 * The author and invisible participants are not included in the count.
 *
 * @author      Marcel Werk
 * @copyright   2001-2026 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.3
 */
final class UpdateConversationParticipantCounts
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
        $conditionBuilder->add('conversation.conversationID IN (?)', [$this->conversationIDs]);

        $sql = "UPDATE  wcf1_conversation conversation
                SET     participants = (
                            SELECT  COUNT(*) AS count
                            FROM    wcf1_conversation_to_user conversation_to_user
                            WHERE   conversation_to_user.conversationID = conversation.conversationID
                                AND conversation_to_user.hideConversation <> ?
                                AND conversation_to_user.participantID <> conversation.userID
                                AND conversation_to_user.isInvisible = ?
                        )
                " . $conditionBuilder;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([
            Conversation::STATE_LEFT,
            0,
            ...$conditionBuilder->getParameters(),
        ]);
    }
}
