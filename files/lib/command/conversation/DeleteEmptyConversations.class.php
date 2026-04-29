<?php

namespace wcf\command\conversation;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationAction;
use wcf\data\conversation\ConversationEditor;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * Deletes conversations if all users have left them.
 *
 * @author      Olaf Braun
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 */
final class DeleteEmptyConversations
{
    /**
     * @param int[] $conversationIDs
     */
    public function __construct(
        public readonly array $conversationIDs,
    ) {}

    public function __invoke(): void
    {
        // update participants count and participant summary
        ConversationEditor::updateParticipantCounts($this->conversationIDs);

        $conditionBuilder = new PreparedStatementConditionBuilder();
        $conditionBuilder->add('conversation.conversationID IN (?)', [$this->conversationIDs]);
        $conditionBuilder->add('conversation_to_user.conversationID IS NULL');
        $sql = "SELECT      DISTINCT conversation.conversationID
                    FROM        wcf1_conversation conversation
                    LEFT JOIN   wcf1_conversation_to_user conversation_to_user
                    ON          conversation_to_user.conversationID = conversation.conversationID
                            AND conversation_to_user.hideConversation <> " . Conversation::STATE_LEFT . "
                            AND conversation_to_user.participantID IS NOT NULL
                    " . $conditionBuilder;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditionBuilder->getParameters());
        $conversationIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);

        if ($conversationIDs !== []) {
            $action = new ConversationAction($conversationIDs, 'delete');
            $action->executeAction();
        }
    }
}
