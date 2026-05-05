<?php

namespace wcf\form;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationAction;
use wcf\data\IStorableObject;
use wcf\data\user\group\UserGroup;
use wcf\data\user\UserProfile;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\conversation\TConversationForm;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\NamedUserException;
use wcf\system\flood\FloodControl;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\container\wysiwyg\WysiwygFormContainer;
use wcf\system\form\builder\data\processor\CustomFormDataProcessor;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\MultipleSelectionFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\field\user\UserFormField;
use wcf\system\form\builder\field\validation\FormFieldValidationError;
use wcf\system\form\builder\field\validation\FormFieldValidator;
use wcf\system\form\builder\IFormDocument;
use wcf\system\page\PageLocationManager;
use wcf\system\WCF;
use wcf\util\HeaderUtil;

/**
 * Shows the conversation form.
 *
 * @author      Olaf Braun, Marcel Werk
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractFormBuilderForm<Conversation>
 */
class ConversationAddForm extends AbstractFormBuilderForm
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

    /**
     * @inheritDoc
     */
    public $objectActionClass = ConversationAction::class;

    protected ?UserProfile $user = null;

    #[\Override]
    public function readData()
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
    public function createForm()
    {
        parent::createForm();

        $groupParticipants = \array_filter(
            UserGroup::getSortedGroupsByType(),
            // @phpstan-ignore property.notFound
            static fn(UserGroup $group) => $group->canBeAddedAsConversationParticipant
        );

        $this->form->appendChildren([
            FormContainer::create('informationContainer')
                ->label('wcf.conversation.information')
                ->appendChildren([
                    TextFormField::create('subject')
                        ->label('wcf.global.subject')
                        ->maximumLength(255)
                        ->censorship()
                        ->required(),
                    BooleanFormField::create('isDraft')
                        ->label('wcf.conversation.form.isDraft'),
                ]),
            FormContainer::create('participantsContainer')
                ->label('wcf.conversation.participants')
                ->appendChildren([
                    UserFormField::create('participants')
                        ->label('wcf.conversation.participants')
                        ->maximumMultiples(WCF::getSession()->getPermission('user.conversation.maxParticipants'))
                        ->addValidator($this->getParticipantsValidator())
                        ->addValidator($this->getMaximumParticipantsValidator())
                        ->value($this->user ? [$this->user->userID] : []),
                    MultipleSelectionFormField::create('participantGroups')
                        ->label('wcf.conversation.participantGroups')
                        ->available(WCF::getSession()->getPermission('user.conversation.canAddGroupParticipants')
                            && \count($groupParticipants) > 0)
                        ->filterable(\count($groupParticipants) > 20)
                        ->options($groupParticipants),
                    UserFormField::create('invisibleParticipants')
                        ->label('wcf.conversation.invisibleParticipants')
                        ->description('wcf.conversation.invisibleParticipants.description')
                        ->available(WCF::getSession()->getPermission('user.conversation.canAddInvisibleParticipants'))
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
                                                        'username' => $user->username,
                                                    ]
                                                )
                                            );
                                        }
                                    }
                                }
                            )
                        ),
                    MultipleSelectionFormField::create('invisibleParticipantGroups')
                        ->label('wcf.conversation.invisibleParticipantGroups')
                        ->available(
                            WCF::getSession()->getPermission('user.conversation.canAddInvisibleParticipants')
                                && WCF::getSession()->getPermission('user.conversation.canAddGroupParticipants')
                                && \count($groupParticipants) > 0
                        )
                        ->filterable(\count($groupParticipants) > 20)
                        ->options($groupParticipants),
                    BooleanFormField::create('participantCanInvite')
                        ->label('wcf.conversation.participantCanInvite')
                        ->available(WCF::getSession()->getPermission('user.conversation.canSetCanInvite')),
                ]),
            WysiwygFormContainer::create('message')
                ->label('wcf.conversation.message')
                ->messageObjectType('com.woltlab.wcf.conversation.message')
                ->attachmentData('com.woltlab.wcf.conversation.message')
                ->supportMentions()
                ->supportQuotes()
                ->required(),
        ]);
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
    protected function finalizeForm()
    {
        parent::finalizeForm();

        $this->form->getDataHandler()
            ->addProcessor(
                new CustomFormDataProcessor('messageProcessor', static function (IFormDocument $document, array $parameters) {
                    unset($parameters['data']['message']);

                    return $parameters;
                }, static function (IFormDocument $document, array $data, IStorableObject $object) {
                    \assert($object instanceof Conversation);
                    $data['message'] = $object->getFirstMessage()->message;

                    return $data;
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
                            $participants = \array_unique(
                                \array_merge(
                                    $participants,
                                    $this->getUserByGroups($groupIDs)
                                )
                            );
                        }

                        if (isset($parameters['invisibleParticipantGroups'])) {
                            $groupIDs = $parameters['invisibleParticipantGroups'];
                            $userIDs = $this->getUserByGroups($groupIDs);

                            $invisibleParticipants = \array_unique(
                                \array_merge(
                                    $invisibleParticipants,
                                    // filtere all users that are already in participants
                                    \array_diff($userIDs, $participants)
                                )
                            );
                        }

                        $parameters['participants'] = $participants;
                        $parameters['invisibleParticipants'] = $invisibleParticipants;

                        return $parameters;
                    }
                )
            )
            ->addProcessor(
                new CustomFormDataProcessor(
                    'draftDataProcessor',
                    static function (IFormDocument $document, array $parameters) {
                        if ($parameters['data']['isDraft']) {
                            $parameters['data']['draftData'] = \serialize([
                                'participants' => $parameters['participants'] ?? [],
                                'invisibleParticipants' => $parameters['invisibleParticipants'] ?? [],
                            ]);

                            unset($parameters['participants'], $parameters['invisibleParticipants']);
                        } else {
                            $parameters['data']['draftData'] = \serialize([]);
                        }

                        return $parameters;
                    },
                    static function (IFormDocument $document, array $data, IStorableObject $object) {
                        \assert($object instanceof Conversation);

                        $draftData = @\unserialize($object->draftData);

                        $data['participants'] = $draftData['participants'];
                        $data['invisibleParticipants'] = $draftData['invisibleParticipants'];

                        return $data;
                    }
                )
            );
    }

    #[\Override]
    public function saved()
    {
        parent::saved();

        if ($this->formAction === 'create') {
            $conversation = $this->objectAction->getReturnValues()['returnValues'];
            \assert($conversation instanceof Conversation);
        } else {
            $conversation = new Conversation($this->formObject->conversationID);
        }

        if (!$conversation->isDraft) {
            FloodControl::getInstance()->registerContent('com.woltlab.wcf.conversation');
            FloodControl::getInstance()->registerContent('com.woltlab.wcf.conversation.message');
        }

        HeaderUtil::redirect($conversation->getLink());

        exit;
    }
}
