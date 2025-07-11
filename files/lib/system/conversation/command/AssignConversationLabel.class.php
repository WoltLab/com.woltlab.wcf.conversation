<?php

namespace wcf\system\conversation\command;

use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * @author Olaf Braun
 * @copyright 2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
final class AssignConversationLabel
{
    public function __construct(
        /**
         * @var int[]
         */
        public readonly array $oldLabelIDs,
        /**
         * @var int[]
         */
        public readonly array $conversationIDs,
        /**
         * @var int[]
         */
        public readonly array $newLabelIDs
    ) {}

    public function __invoke(): void
    {
        $this->removeOldLabels($this->conversationIDs, $this->oldLabelIDs);
        $this->assignLabels($this->conversationIDs, $this->newLabelIDs);
    }

    /**
     * @param int[] $conversationIDs
     * @param int[] $labelIDs
     */
    private function removeOldLabels(array $conversationIDs, array $labelIDs): void
    {
        if ($labelIDs === []) {
            return;
        }

        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add("conversationID IN (?)", [$conversationIDs]);
        $conditions->add("labelID IN (?)", [$labelIDs]);

        $sql = "DELETE FROM wcf1_conversation_label_to_object
                " . $conditions;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditions->getParameters());
    }

    /**
     * @param int[] $conversationIDs
     * @param int[] $labelIDs
     */
    private function assignLabels(array $conversationIDs, array $labelIDs): void
    {
        if ($labelIDs === []) {
            return;
        }

        $sql = "INSERT INTO wcf1_conversation_label_to_object
                            (labelID, conversationID)
                VALUES      (?, ?)";
        $statement = WCF::getDB()->prepare($sql);

        foreach ($labelIDs as $labelID) {
            foreach ($conversationIDs as $conversationID) {
                $statement->execute([
                    $labelID,
                    $conversationID,
                ]);
            }
        }
    }
}
