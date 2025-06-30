<?php

namespace wcf\system\conversation;

use wcf\data\conversation\Conversation;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\UserInputException;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\MultipleSelectionFormField;
use wcf\system\form\builder\field\user\UserFormField;
use wcf\system\form\builder\field\validation\FormFieldValidationError;
use wcf\system\form\builder\field\validation\FormFieldValidator;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;

/**
 * Provides methods that can be used in conversation form builder forms.
 *
 * @author Olaf Braun
 * @copyright 2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
trait TConversationForm
{
    /**
     * Returns the user IDs of the users that are in the given groups.
     *
     * @param int[] $groupIDs
     *
     * @return int[]
     */
    protected function getUserByGroups(array $groupIDs): array
    {
        if ($groupIDs === []) {
            return [];
        }

        $conditionBuilder = new PreparedStatementConditionBuilder();
        $conditionBuilder->add('groupID IN (?)', [$groupIDs]);
        $sql = "SELECT  DISTINCT userID
                FROM    wcf1_user_to_group
                " . $conditionBuilder;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditionBuilder->getParameters());

        $userIDs = [];
        while ($userID = $statement->fetchColumn()) {
            $userIDs[] = $userID;
        }

        return $userIDs;
    }

    /**
     * Returns a validator that checks if the selected participants are valid.
     */
    protected function getParticipantsValidator(): FormFieldValidator
    {
        return new FormFieldValidator('participantsValidator', static function (UserFormField $formField) {
            $users = $formField->getUsers();
            $userIDs = \array_column($users, 'userID');

            UserStorageHandler::getInstance()->loadStorage($userIDs);

            foreach ($users as $user) {
                try {
                    if ($user->userID === WCF::getUser()->userID) {
                        throw new UserInputException('isAuthor');
                    }

                    Conversation::validateParticipant($user, $formField->getId());
                } catch (UserInputException $e) {
                    $formField->addValidationError(
                        new FormFieldValidationError(
                            $e->getType(),
                            'wcf.conversation.participants.error.' . $e->getType(),
                            [
                                'username' => $user->username,
                            ]
                        )
                    );
                }
            }
        });
    }

    /**
     * Returns a validator that checks if the maximum number of participants is not exceeded.
     */
    protected function getMaximumParticipantsValidator(
        string $invisibleParticipantsFieldId = 'invisibleParticipants',
        string $participantGroupsFieldId = 'participantGroups',
        ?string $invisibleParticipantGroupsFieldId = 'invisibleParticipantGroups'
    ): FormFieldValidator {
        return new FormFieldValidator(
            'participantsMaximumValidator',
            function (UserFormField $formField) use (
                $invisibleParticipantsFieldId,
                $participantGroupsFieldId,
                $invisibleParticipantGroupsFieldId
            ) {
                $invisibleParticipantsFormField = $formField->getDocument()
                    ->getNodeById($invisibleParticipantsFieldId);
                $participantGroupsFormField = $formField->getDocument()
                    ->getNodeById($participantGroupsFieldId);
                $isDraftFormField = $formField->getDocument()->getNodeById('isDraft');
                $invisibleParticipantGroupsFormField = $invisibleParticipantGroupsFieldId !== null ? $formField->getDocument()
                    ->getNodeById($invisibleParticipantGroupsFieldId) : null;

                \assert($invisibleParticipantsFormField === null || $invisibleParticipantsFormField instanceof UserFormField);
                \assert($isDraftFormField === null || $isDraftFormField instanceof BooleanFormField);
                \assert($participantGroupsFormField === null || $participantGroupsFormField instanceof MultipleSelectionFormField);
                \assert($invisibleParticipantGroupsFormField === null || $invisibleParticipantGroupsFormField instanceof MultipleSelectionFormField);

                $groupIDs = \array_merge(
                    $participantGroupsFormField?->getValue() ?: [],
                    $invisibleParticipantGroupsFormField?->getValue() ?: [],
                );

                $userIDs = \array_merge(
                    \array_column($formField->getUsers(), 'userID'),
                    \array_column($invisibleParticipantsFormField?->getUsers() ?: [], 'userID'),
                    $this->getUserByGroups($groupIDs)
                );

                if (\count($userIDs) > WCF::getSession()->getPermission('user.conversation.maxParticipants')) {
                    $formField->addValidationError(
                        new FormFieldValidationError(
                            'tooManyParticipants',
                            'wcf.conversation.participants.error.tooManyParticipants'
                        )
                    );
                }

                if (!$isDraftFormField?->getValue() && $userIDs === []) {
                    $formField->addValidationError(new FormFieldValidationError('empty'));
                }
            }
        );
    }
}
