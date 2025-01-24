<?php

namespace wcf\form;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationAction;
use wcf\data\user\group\UserGroup;
use wcf\system\cache\builder\UserGroupCacheBuilder;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\exception\UserInputException;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\container\wysiwyg\WysiwygFormContainer;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\dependency\NonEmptyFormFieldDependency;
use wcf\system\form\builder\field\MultipleSelectionFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\field\user\UserFormField;
use wcf\system\form\builder\field\validation\FormFieldValidationError;
use wcf\system\form\builder\field\validation\FormFieldValidator;
use wcf\system\page\PageLocationManager;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;

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
                        ->addValidator(ConversationAddForm::getParticipantsValidator()),
                    BooleanFormField::create('addGroupParticipants')
                        ->label('wcf.conversation.addGroupParticipants')
                        ->available(\count($groupParticipants) > 0),
                    MultipleSelectionFormField::create('participantGroups')
                        ->label('wcf.conversation.participantGroups')
                        ->available(WCF::getSession()->getPermission('user.conversation.canAddGroupParticipants'))
                        ->filterable()
                        ->options($groupParticipants)
                        ->addValidator(ConversationAddForm::getGroupParticipantsValidator('participants'))
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
                        ->addValidator(ConversationAddForm::getGroupParticipantsValidator('invisibleParticipants'))
                        ->addDependency(
                            NonEmptyFormFieldDependency::create('addInvisibleGroupParticipantsDependency')
                                ->fieldId('addInvisibleGroupParticipants')
                        ),
                    BooleanFormField::create('participantCanInvite')
                        ->label('wcf.conversation.participantCanInvite')
                        ->available(WCF::getSession()->getPermission('user.conversation.canSetCanInvite'))
                ]),
            WysiwygFormContainer::create('text')
                ->label('wcf.conversation.message')
                ->messageObjectType('com.woltlab.wcf.conversation.message')
                ->attachmentData('com.woltlab.wcf.conversation.message')
                ->supportMentions()
                ->supportQuotes()
                ->required()
        ]);
        // TODO validate participants count
        // TODO add dataHandler to merge participants and participantGroups
        // TODO add dataHandler to merge invisibleParticipants and invisibleParticipantGroups
    }

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

    public static function getGroupParticipantsValidator(string $participantsNodeId): FormFieldValidator
    {
        return new FormFieldValidator(
            'groupParticipantsValidator',
            function (MultipleSelectionFormField $formField) use ($participantsNodeId) {
                $groupIDs = $formField->getValue();

                $validGroupParticipants = Conversation::validateGroupParticipants($groupIDs, $formField->getId());

                /** @var $participantsFormField UserFormField */
                $participantsFormField = $formField->getDocument()->getNodeById($participantsNodeId);
                $participantIDs = \array_column($participantsFormField->getUsers(), 'userID');

                $validGroupParticipants = \array_diff($validGroupParticipants, $participantIDs);
                if (empty($validGroupParticipants)) {
                    $formField->addValidationError(
                        new FormFieldValidationError(
                            'emptyGroup',
                            'wcf.conversation.participants.error.emptyGroup'
                        )
                    );
                }
            }
        );
    }
}
