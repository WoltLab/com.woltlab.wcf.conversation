<?php

namespace wcf\form;

use Laminas\Diactoros\Response\RedirectResponse;
use wcf\command\conversation\CreateConversation;
use wcf\command\conversation\UpdateConversation;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationBuilder;
use wcf\data\DatabaseObjectBuilder;
use wcf\data\user\group\UserGroup;
use wcf\data\user\UserProfile;
use wcf\system\cache\builder\UserGroupCacheBuilder;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\conversation\TConversationForm;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\NamedUserException;
use wcf\system\flood\FloodControl;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\container\wysiwyg\WysiwygFormContainer;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\IFormField;
use wcf\system\form\builder\field\MultipleSelectionFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\field\user\UserFormField;
use wcf\system\form\builder\field\validation\FormFieldValidationError;
use wcf\system\form\builder\field\validation\FormFieldValidator;
use wcf\system\form\builder\field\wysiwyg\WysiwygAttachmentFormField;
use wcf\system\form\builder\field\wysiwyg\WysiwygFormField;
use wcf\system\page\PageLocationManager;
use wcf\system\WCF;

/**
 * Shows the conversation form.
 *
 * @author      Olaf Braun, Marcel Werk
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractDatabaseObjectBuilderForm<Conversation, ConversationBuilder>
 */
class ConversationAddForm extends AbstractDatabaseObjectBuilderForm
{
    use TConversationForm;

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

    protected ?UserProfile $user = null;

    #[\Override]
    public function readData(): void
    {
        parent::readData();

        PageLocationManager::getInstance()->addParentLocation('com.woltlab.wcf.conversation.ConversationList');
    }

    #[\Override]
    public function readParameters()
    {
        parent::readParameters();

        if (isset($_REQUEST['userID'])) {
            $userID = \intval($_REQUEST['userID']);
            $this->user = UserProfileRuntimeCache::getInstance()->getObject($userID);
            if ($this->user === null || $this->user->userID === WCF::getUser()->userID) {
                throw new IllegalLinkException();
            }

            $error = $this->isValidParticipant($this->user);
            if ($error !== null) {
                throw new NamedUserException($error->getMessage());
            }
        }
    }

    #[\Override]
    protected function createForm(): void
    {
        parent::createForm();

        $groupParticipants = \array_filter(
            UserGroupCacheBuilder::getInstance()->getData([], 'groups'),
            static function (UserGroup $group) {
                // @phpstan-ignore property.notFound
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
                        ->censorship()
                        ->required()
                        ->saveValueCallback(static function (ConversationBuilder $builder, IFormField $field) {
                            $builder->setSubject((string)$field->getSaveValue());
                        }),
                    BooleanFormField::create('isDraft')
                        ->label('wcf.conversation.form.isDraft')
                        ->saveValueCallback(static function (ConversationBuilder $builder, IFormField $field) {
                            $builder->setIsDraft((bool)$field->getSaveValue());
                        }),
                ]),
            // The save value callbacks of the participant fields are applied in
            // the order of the fields: the groups are merged into the users
            // selected before and invisible participants exclude the
            // participants.
            FormContainer::create('participantsContainer')
                ->label('wcf.conversation.participants')
                ->appendChildren([
                    UserFormField::create('participants')
                        ->label('wcf.conversation.participants')
                        ->maximumMultiples(WCF::getSession()->getPermission('user.conversation.maxParticipants'))
                        ->addValidator($this->getParticipantsValidator())
                        ->addValidator($this->getMaximumParticipantsValidator())
                        ->value($this->user !== null ? [$this->user->userID] : [])
                        ->saveValueCallback(static function (ConversationBuilder $builder, UserFormField $field) {
                            $builder->setParticipantIDs(\array_column($field->getUsers(), 'userID'));
                        })
                        ->loadValueCallback(static function (Conversation $object, IFormField $field) {
                            $field->value(self::getDraftParticipantIDs($object, 'participants'));
                        }),
                    MultipleSelectionFormField::create('participantGroups')
                        ->label('wcf.conversation.participantGroups')
                        ->available(WCF::getSession()->hasPermission('user.conversation.canAddGroupParticipants')
                            && \count($groupParticipants) > 0)
                        ->filterable(\count($groupParticipants) > 20)
                        ->options($groupParticipants)
                        ->saveValueCallback(function (ConversationBuilder $builder, IFormField $field) {
                            $builder->setParticipantIDs(\array_unique(\array_merge(
                                $builder->participantIDs ?? [],
                                $this->getUserByGroups($field->getSaveValue() ?: [])
                            )));
                        }),
                    UserFormField::create('invisibleParticipants')
                        ->label('wcf.conversation.invisibleParticipants')
                        ->description('wcf.conversation.invisibleParticipants.description')
                        ->available(WCF::getSession()->hasPermission('user.conversation.canAddInvisibleParticipants'))
                        ->maximumMultiples(WCF::getSession()->getPermission('user.conversation.maxParticipants'))
                        ->addValidator($this->getParticipantsValidator())
                        ->addValidator(
                            new FormFieldValidator(
                                'duplicateParticipantsValidator',
                                static function (UserFormField $formField) {
                                    /** @var UserFormField $participantsFormField */
                                    $participantsFormField = $formField->getDocument()->getNodeById('participants');

                                    $participants = \array_column($participantsFormField->getUsers(), 'userID');
                                    $invisibleParticipants = \array_column($formField->getUsers(), 'userID');

                                    $intersection = \array_intersect($participants, $invisibleParticipants);
                                    if ($intersection !== []) {
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
                                                        'username' => $user->username,
                                                    ]
                                                )
                                            );
                                        }
                                    }
                                }
                            )
                        )
                        ->saveValueCallback(static function (ConversationBuilder $builder, UserFormField $field) {
                            $builder->setInvisibleParticipantIDs(\array_column($field->getUsers(), 'userID'));
                        })
                        ->loadValueCallback(static function (Conversation $object, IFormField $field) {
                            $field->value(self::getDraftParticipantIDs($object, 'invisibleParticipants'));
                        }),
                    MultipleSelectionFormField::create('invisibleParticipantGroups')
                        ->label('wcf.conversation.invisibleParticipantGroups')
                        ->available(
                            WCF::getSession()->hasPermission('user.conversation.canAddInvisibleParticipants')
                                && WCF::getSession()->hasPermission('user.conversation.canAddGroupParticipants')
                                && \count($groupParticipants) > 0
                        )
                        ->filterable(\count($groupParticipants) > 20)
                        ->options($groupParticipants)
                        ->saveValueCallback(function (ConversationBuilder $builder, IFormField $field) {
                            $builder->setInvisibleParticipantIDs(\array_unique(\array_merge(
                                $builder->invisibleParticipantIDs ?? [],
                                \array_diff(
                                    $this->getUserByGroups($field->getSaveValue() ?: []),
                                    $builder->participantIDs ?? []
                                )
                            )));
                        }),
                    BooleanFormField::create('participantCanInvite')
                        ->label('wcf.conversation.participantCanInvite')
                        ->available(WCF::getSession()->hasPermission('user.conversation.canSetCanInvite'))
                        ->saveValueCallback(static function (ConversationBuilder $builder, IFormField $field) {
                            $builder->setParticipantCanInvite((bool)$field->getSaveValue());
                        }),
                ]),
            $this->createMessageContainer(),
        ]);
    }

    protected function createMessageContainer(): WysiwygFormContainer
    {
        $container = WysiwygFormContainer::create('message')
            ->label('wcf.conversation.message')
            ->messageObjectType('com.woltlab.wcf.conversation.message')
            ->attachmentData('com.woltlab.wcf.conversation.message', objectID: $this->getAttachmentObjectID())
            ->supportMentions()
            ->supportQuotes()
            ->required();

        $container->getWysiwygField()
            ->saveValueCallback(static function (ConversationBuilder $builder, WysiwygFormField $field) {
                $builder->getMessageBuilder()->setHtmlInputProcessor($field->getHtmlInputProcessor());
            })
            ->loadValueCallback(static function (Conversation $object, IFormField $field) {
                $field->value($object->getFirstMessage()?->message);
            });
        $container->getAttachmentField()->saveValueCallback(
            static function (ConversationBuilder $builder, WysiwygAttachmentFormField $field) {
                $builder->getMessageBuilder()->setAttachmentHandler($field->getAttachmentHandler());
            }
        );

        return $container;
    }

    /**
     * Returns the id of the message whose attachments are edited, `null` when
     * creating a new conversation.
     */
    protected function getAttachmentObjectID(): ?int
    {
        return null;
    }

    #[\Override]
    protected function getDatabaseObjectBuilder(): ConversationBuilder
    {
        if ($this->formObject !== null) {
            return ConversationBuilder::forUpdate($this->formObject);
        }

        return ConversationBuilder::forCreate()
            ->setTime(\TIME_NOW)
            ->setUserID(WCF::getUser()->userID)
            ->setUsername(WCF::getUser()->username);
    }

    #[\Override]
    protected function getCommand(DatabaseObjectBuilder $builder): callable
    {
        if ($builder->isUpdate()) {
            return new UpdateConversation($builder);
        }

        return new CreateConversation($builder);
    }

    /**
     * @param 'participants'|'invisibleParticipants' $type
     * @return int[]
     */
    private static function getDraftParticipantIDs(Conversation $conversation, string $type): array
    {
        $draftData = @\unserialize($conversation->draftData ?? '');
        if (!\is_array($draftData)) {
            return [];
        }

        return $draftData[$type] ?? [];
    }

    #[\Override]
    public function saved(): void
    {
        parent::saved();

        $conversation = $this->object;
        \assert($conversation instanceof Conversation);

        if ($conversation->isDraft === 0) {
            FloodControl::getInstance()->registerContent('com.woltlab.wcf.conversation');
            FloodControl::getInstance()->registerContent('com.woltlab.wcf.conversation.message');
        }

        $this->setPsr7Response(
            new RedirectResponse(
                $conversation->getLink(),
            )
        );
    }
}
