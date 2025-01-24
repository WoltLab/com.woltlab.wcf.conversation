<?php

namespace wcf\form;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationAction;
use wcf\data\IStorableObject;
use wcf\data\user\group\UserGroup;
use wcf\system\cache\builder\UserGroupCacheBuilder;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\UserInputException;
use wcf\system\flood\FloodControl;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\container\wysiwyg\WysiwygFormContainer;
use wcf\system\form\builder\data\processor\CustomFormDataProcessor;
use wcf\system\form\builder\data\processor\VoidFormDataProcessor;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\dependency\NonEmptyFormFieldDependency;
use wcf\system\form\builder\field\MultipleSelectionFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\field\user\UserFormField;
use wcf\system\form\builder\field\validation\FormFieldValidationError;
use wcf\system\form\builder\field\validation\FormFieldValidator;
use wcf\system\form\builder\IFormDocument;
use wcf\system\page\PageLocationManager;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;
use wcf\util\HeaderUtil;

/**
 * Shows the conversation form.
 *
 * @author      Olaf Braun, Marcel Werk
 * @copyright   2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @property Conversation $formObject
 */
class ConversationAddForm extends AbstractFormBuilderForm
{
    /**
     * @inheritDoc
     */
    public $loginRequired = true;

    /**
     * @inheritDoc
     */
    public $neededModules = ['MODULE_CONVERSATION'];

    /**
     * @inheritDoc
     */
    public $neededPermissions = ['user.conversation.canUseConversation'];

    /**
     * @inheritDoc
     */
    public $objectActionClass = ConversationAction::class;

    /**
     * @inheritDoc
     */
    public function readData()
    {
        parent::readData();

        // add breadcrumbs
        PageLocationManager::getInstance()->addParentLocation('com.woltlab.wcf.conversation.ConversationList');
    }

    #[\Override]
    public function createForm()
    {
        parent::createForm();

        $groupParticipants = \array_filter(
            UserGroupCacheBuilder::getInstance()->getData([], 'groups'),
            function (UserGroup $group) {
                return $group->canBeAddedAsConversationParticipant;
            }
        );

        $this->form->appendChildren([
            FormContainer::create('informationContainer')
                ->label('wcf.conversation.information')
                ->appendChildren([
                    TextFormField::create('subject')
                        ->label('wcf.global.subject')
                        ->maximumLength(255)
                        ->required(),
                    BooleanFormField::create('isDraft')
                        ->label('wcf.conversation.form.isDraft'),
                ]),
            FormContainer::create('participantsContainer')
                ->label('wcf.conversation.participants')
                ->appendChildren([
                    UserFormField::create('participants')
                        ->label('wcf.conversation.participants')
                        ->description('wcf.conversation.participants.description')
                        ->maximumMultiples(WCF::getSession()->getPermission('user.conversation.maxParticipants'))
                        ->addValidator(ConversationAddForm::getParticipantsValidator())
                        ->addValidator(ConversationAddForm::getMaximumParticipantsValidator()),
                    BooleanFormField::create('addGroupParticipants')
                        ->label('wcf.conversation.addGroupParticipants')
                        ->available(\count($groupParticipants) > 0),
                    MultipleSelectionFormField::create('participantGroups')
                        ->label('wcf.conversation.participantGroups')
                        ->available(WCF::getSession()->getPermission('user.conversation.canAddGroupParticipants'))
                        ->filterable()
                        ->options($groupParticipants)
                        ->addDependency(
                            NonEmptyFormFieldDependency::create('addGroupParticipantsDependency')
                                ->fieldId('addGroupParticipants')
                        ),
                    UserFormField::create('invisibleParticipants')
                        ->label('wcf.conversation.invisibleParticipants')
                        ->description('wcf.conversation.invisibleParticipants.description')
                        ->available(WCF::getSession()->getPermission('user.conversation.canAddInvisibleParticipants'))
                        ->maximumMultiples(WCF::getSession()->getPermission('user.conversation.maxParticipants'))
                        ->addValidator(ConversationAddForm::getParticipantsValidator())
                        ->addValidator(
                            new FormFieldValidator(
                                'duplicateParticipantsValidator',
                                function (UserFormField $formField) {
                                    /** @var UserFormField $participantsFormField */
                                    $participantsFormField = $formField->getDocument()->getNodeById('participants');

                                    $participants = \array_column($participantsFormField->getUsers(), 'userID');
                                    $invisibleParticipants = \array_column($formField->getUsers(), 'userID');

                                    $intersection = \array_intersect($participants, $invisibleParticipants);
                                    if (!empty($intersection)) {
                                        foreach (
                                            UserProfileRuntimeCache::getInstance()->getObjects(
                                                \array_slice($intersection, 0, 10)
                                            ) as $user
                                        ) {
                                            $formField->addValidationError(
                                                new FormFieldValidationError(
                                                    'intersects',
                                                    'wcf.conversation.participants.error.intersects',
                                                    [
                                                        'username' => $user->username
                                                    ]
                                                )
                                            );
                                        }
                                    }
                                }
                            )
                        ),
                    BooleanFormField::create('addInvisibleGroupParticipants')
                        ->label('wcf.conversation.addInvisibleGroupParticipants')
                        ->available(
                            \count($groupParticipants) > 0
                            && WCF::getSession()->getPermission('user.conversation.canAddInvisibleParticipants')
                        ),
                    MultipleSelectionFormField::create('invisibleParticipantGroups')
                        ->label('wcf.conversation.invisibleParticipantGroups')
                        ->available(
                            WCF::getSession()->getPermission('user.conversation.canAddInvisibleParticipants')
                            && WCF::getSession()->getPermission('user.conversation.canAddGroupParticipants')
                        )
                        ->filterable()
                        ->options($groupParticipants)
                        ->addDependency(
                            NonEmptyFormFieldDependency::create('addInvisibleGroupParticipantsDependency')
                                ->fieldId('addInvisibleGroupParticipants')
                        ),
                    BooleanFormField::create('participantCanInvite')
                        ->label('wcf.conversation.participantCanInvite')
                        ->available(WCF::getSession()->getPermission('user.conversation.canSetCanInvite'))
                ]),
            WysiwygFormContainer::create('message')
                ->label('wcf.conversation.message')
                ->messageObjectType('com.woltlab.wcf.conversation.message')
                ->attachmentData('com.woltlab.wcf.conversation.message')
                ->supportMentions()
                ->supportQuotes()
                ->required()
        ]);

        $this->form->getDataHandler()
            ->addProcessor(new VoidFormDataProcessor('addGroupParticipants'))
            ->addProcessor(new VoidFormDataProcessor('addInvisibleGroupParticipants'))
            ->addProcessor(
                new CustomFormDataProcessor('messageProcessor', function (IFormDocument $document, array $parameters) {
                    unset($parameters['data']['message']);

                    return $parameters;
                }, function (IFormDocument $document, array $parameters, IStorableObject $object) {
                    \assert($object instanceof Conversation);
                    $parameters['data']['message'] = $object->getFirstMessage()->message;

                    return $parameters;
                })
            )
            ->addProcessor(
                new CustomFormDataProcessor(
                    'participantsProcessor',
                    function (IFormDocument $document, array $parameters) {
                        $participants = $parameters['participants'] ?? [];
                        $invisibleParticipants = $parameters['invisibleParticipants'] ?? [];

                        if (isset($parameters['participantGroups'])) {
                            $groupIDs = $parameters['participantGroups'];
                            $participants = \array_merge(
                                $participants,
                                ConversationAddForm::getUserByGroups($groupIDs)
                            );
                        }

                        if (isset($parameters['invisibleParticipantGroups'])) {
                            $groupIDs = $parameters['invisibleParticipantGroups'];
                            $userIDs = ConversationAddForm::getUserByGroups($groupIDs);

                            $invisibleParticipants = \array_merge(
                                $invisibleParticipants,
                                // filtere all users that are already in participants
                                \array_diff($userIDs, $participants)
                            );
                        }

                        $parameters['participants'] = $participants;
                        $parameters['invisibleParticipants'] = $invisibleParticipants;

                        return $parameters;
                    }
                )
            );
    }

    #[\Override]
    public function save()
    {
        $this->additionalFields = [
            'time' => TIME_NOW,
            'userID' => WCF::getUser()->userID,
            'username' => WCF::getUser()->username,
        ];

        parent::save();
    }

    #[\Override]
    public function saved()
    {
        parent::saved();

        /** @var Conversation $conversation */
        $conversation = $this->objectAction->getReturnValues()['returnValues'];

        if (!$conversation->isDraft) {
            FloodControl::getInstance()->registerContent('com.woltlab.wcf.conversation');
            FloodControl::getInstance()->registerContent('com.woltlab.wcf.conversation.message');
        }

        // forward
        HeaderUtil::redirect($conversation->getLink());

        exit;
    }

    /**
     * Returns a validator that checks if the selected participants are valid.
     *
     * @since 6.2
     */
    public static function getParticipantsValidator(): FormFieldValidator
    {
        return new FormFieldValidator('participantsValidator', function (UserFormField $formField) {
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
                                'username' => $user->username
                            ]
                        )
                    );
                }
            }
        });
    }

    /**
     * Returns a validator that checks if the maximum number of participants is not exceeded.
     *
     * @since 6.2
     */
    public static function getMaximumParticipantsValidator(
        string $invisibleParticipantsFieldId = 'invisibleParticipants',
        string $participantGroupsFieldId = 'participantGroups',
        string $invisibleParticipantGroupsFieldId = 'invisibleParticipantGroups'
    ): FormFieldValidator {
        return new FormFieldValidator(
            'participantsMaximumValidator',
            function (UserFormField $formField) use (
                $invisibleParticipantsFieldId,
                $participantGroupsFieldId,
                $invisibleParticipantGroupsFieldId
            ) {
                /**
                 * @var UserFormField|null              $invisibleParticipantsFormField
                 * @var MultipleSelectionFormField|null $participantGroupsFormField
                 * @var MultipleSelectionFormField|null $invisibleParticipantGroupsFormField
                 */

                $invisibleParticipantsFormField = $formField->getDocument()
                    ->getNodeById($invisibleParticipantsFieldId);
                $participantGroupsFormField = $formField->getDocument()
                    ->getNodeById($participantGroupsFieldId);
                $invisibleParticipantGroupsFormField = $formField->getDocument()
                    ->getNodeById($invisibleParticipantGroupsFieldId);

                $groupIDs = \array_merge(
                    $participantGroupsFormField?->getValue() ?: [],
                    $invisibleParticipantGroupsFormField?->getValue() ?: [],
                );
                $userIDs = \array_merge(
                    \array_column($formField->getUsers(), 'userID'),
                    \array_column($invisibleParticipantsFormField?->getUsers() ?: [], 'userID'),
                    ConversationAddForm::getUserByGroups($groupIDs)
                );

                if (\count($userIDs) > WCF::getSession()->getPermission('user.conversation.maxParticipants')) {
                    $formField->addValidationError(
                        new FormFieldValidationError(
                            'tooManyParticipants',
                            'wcf.conversation.participants.error.tooManyParticipants'
                        )
                    );
                }
            }
        );
    }

    /**
     * Returns the user IDs of the users that are in the given groups.
     *
     * @return int[]
     */
    public static function getUserByGroups(array $groupIDs): array
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
}
