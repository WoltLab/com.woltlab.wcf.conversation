<?php

namespace wcf\form;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationAction;
use wcf\data\user\group\UserGroup;
use wcf\system\cache\builder\UserGroupCacheBuilder;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\conversation\ConversationHandler;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\NamedUserException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\flood\FloodControl;
use wcf\system\message\quote\MessageQuoteManager;
use wcf\system\page\PageLocationManager;
use wcf\system\WCF;
use wcf\util\ArrayUtil;
use wcf\util\HeaderUtil;
use wcf\util\StringUtil;

/**
 * Shows the conversation form.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package WoltLabSuite\Core\Form
 */
class ConversationAddForm extends MessageForm
{
    /**
     * @inheritDoc
     */
    public $attachmentObjectType = 'com.woltlab.wcf.conversation.message';

    /**
     * @inheritDoc
     */
    public $loginRequired = true;

    /**
     * @inheritDoc
     */
    public $messageObjectType = 'com.woltlab.wcf.conversation.message';

    /**
     * @inheritDoc
     */
    public $neededModules = ['MODULE_CONVERSATION'];

    /**
     * @inheritDoc
     */
    public $neededPermissions = ['user.conversation.canUseConversation'];

    /**
     * participants (comma separated user names)
     * @var string
     */
    public $participants = '';

    /**
     * invisible participants (comma separated user names)
     * @var string
     */
    public $invisibleParticipants = '';

    /**
     * user group participants (comma separated ids)
     * @var string
     */
    public $participantsGroupIDs = '';

    /**
     * invisible user group participants (comma separated ids)
     * @var string
     */
    public $invisibleParticipantsGroupIDs = '';

    /**
     * draft status
     * @var int
     */
    public $draft = 0;

    /**
     * true, if participants can add new participants
     * @var int
     */
    public $participantCanInvite = 0;

    /**
     * participants (user ids)
     * @var int[]
     */
    public $participantIDs = [];

    /**
     * invisible participants (user ids)
     * @var int[]
     */
    public $invisibleParticipantIDs = [];

    /**
     * @inheritDoc
     */
    public function readParameters()
    {
        parent::readParameters();

        if (!WCF::getUser()->userID) {
            return;
        }

        // check max pc permission
        if (ConversationHandler::getInstance()->getConversationCount() >= WCF::getSession()->getPermission('user.conversation.maxConversations')) {
            throw new NamedUserException(WCF::getLanguage()->getDynamicVariable(
                'wcf.conversation.error.mailboxIsFull'
            ));
        }

        ConversationHandler::getInstance()->enforceFloodControl(false);

        if (isset($_REQUEST['userID'])) {
            $userID = \intval($_REQUEST['userID']);
            $user = UserProfileRuntimeCache::getInstance()->getObject($userID);
            if ($user === null || $user->userID == WCF::getUser()->userID) {
                throw new IllegalLinkException();
            }

            // validate user
            try {
                Conversation::validateParticipant($user);
            } catch (UserInputException $e) {
                throw new NamedUserException(WCF::getLanguage()->getDynamicVariable(
                    'wcf.conversation.participants.error.' . $e->getType(),
                    ['errorData' => ['username' => $user->username]]
                ));
            }

            $this->participants = $user->username;
        }

        // get max text length
        $this->maxTextLength = WCF::getSession()->getPermission('user.conversation.maxLength');

        // quotes
        MessageQuoteManager::getInstance()->readParameters();
    }

    /**
     * @inheritDoc
     */
    public function readFormParameters()
    {
        parent::readFormParameters();

        if (isset($_POST['draft'])) {
            $this->draft = (bool)$_POST['draft'];
        }
        if (isset($_POST['participantCanInvite'])) {
            $this->participantCanInvite = (bool)$_POST['participantCanInvite'];
        }
        if (isset($_POST['participants'])) {
            $this->participants = StringUtil::trim($_POST['participants']);
        }
        if (isset($_POST['invisibleParticipants'])) {
            $this->invisibleParticipants = StringUtil::trim($_POST['invisibleParticipants']);
        }
        if (WCF::getSession()->getPermission('user.conversation.canAddGroupParticipants')) {
            if (isset($_POST['participantsGroupIDs'])) {
                $this->participantsGroupIDs = StringUtil::trim($_POST['participantsGroupIDs']);
            }
            if (isset($_POST['invisibleParticipantsGroupIDs'])) {
                $this->invisibleParticipantsGroupIDs = StringUtil::trim($_POST['invisibleParticipantsGroupIDs']);
            }
        }

        // quotes
        MessageQuoteManager::getInstance()->readFormParameters();
    }

    /**
     * @inheritDoc
     */
    public function validate()
    {
        if (
            empty($this->participants)
            && empty($this->invisibleParticipants)
            && empty($this->participantsGroupIDs)
            && empty($this->invisibleParticipantsGroupIDs)
            && !$this->draft
        ) {
            throw new UserInputException('participants');
        }

        // check, if user is allowed to set invisible participants
        if (
            !WCF::getSession()->getPermission('user.conversation.canAddInvisibleParticipants')
            && (!empty($this->invisibleParticipants) || !empty($this->invisibleParticipantsGroupIDs))
        ) {
            throw new UserInputException('participants', 'invisibleParticipantsNoPermission');
        }

        // check, if user is allowed to set participantCanInvite
        if (!WCF::getSession()->getPermission('user.conversation.canSetCanInvite') && $this->participantCanInvite) {
            throw new UserInputException('participantCanInvite', 'participantCanInviteNoPermission');
        }

        $this->participantIDs = Conversation::validateParticipants($this->participants);
        $this->invisibleParticipantIDs = Conversation::validateParticipants(
            $this->invisibleParticipants,
            'invisibleParticipants'
        );
        if (!empty($this->participantsGroupIDs)) {
            $validGroupParticipants = Conversation::validateGroupParticipants($this->participantsGroupIDs);
            $validGroupParticipants = \array_diff($validGroupParticipants, $this->participantIDs);
            if (empty($validGroupParticipants)) {
                throw new UserInputException('participants', 'emptyGroup');
            }
            $this->participantIDs = \array_merge($this->participantIDs, $validGroupParticipants);
        }
        if (!empty($this->invisibleParticipantsGroupIDs)) {
            $validGroupParticipants = Conversation::validateGroupParticipants(
                $this->invisibleParticipantsGroupIDs,
                'invisibleParticipants'
            );
            $validGroupParticipants = \array_diff($validGroupParticipants, $this->invisibleParticipantIDs);
            if (empty($validGroupParticipants)) {
                throw new UserInputException('invisibleParticipants', 'emptyGroup');
            }
            $this->invisibleParticipantIDs = \array_merge($this->invisibleParticipantIDs, $validGroupParticipants);
        }

        // remove duplicates
        $intersection = \array_intersect($this->participantIDs, $this->invisibleParticipantIDs);
        if (!empty($intersection)) {
            $users = UserProfileRuntimeCache::getInstance()->getObjects(\array_slice($intersection, 0, 10));
            throw new UserInputException('invisibleParticipants', \array_map(static function ($user) {
                return [
                    'type' => 'intersects',
                    'username' => $user->username,
                ];
            }, $users));
        }

        if (empty($this->participantIDs) && empty($this->invisibleParticipantIDs) && !$this->draft) {
            throw new UserInputException('participants');
        }

        // check number of participants
        if (\count($this->participantIDs) + \count($this->invisibleParticipantIDs) > WCF::getSession()->getPermission('user.conversation.maxParticipants')) {
            throw new UserInputException('participants', 'tooManyParticipants');
        }

        parent::validate();
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        parent::save();

        // save conversation
        $data = \array_merge($this->additionalFields, [
            'subject' => $this->subject,
            'time' => TIME_NOW,
            'userID' => WCF::getUser()->userID,
            'username' => WCF::getUser()->username,
            'isDraft' => $this->draft ? 1 : 0,
            'participantCanInvite' => $this->participantCanInvite,
        ]);
        if ($this->draft) {
            $data['draftData'] = \serialize([
                'participants' => $this->participantIDs,
                'invisibleParticipants' => $this->invisibleParticipantIDs,
            ]);
        }

        $conversationData = [
            'data' => $data,
            'attachmentHandler' => $this->attachmentHandler,
            'htmlInputProcessor' => $this->htmlInputProcessor,
            'messageData' => [],
        ];
        if (!$this->draft) {
            $conversationData['participants'] = $this->participantIDs;
            $conversationData['invisibleParticipants'] = $this->invisibleParticipantIDs;
        }

        $this->objectAction = new ConversationAction([], 'create', $conversationData);
        /** @var Conversation $conversation */
        $conversation = $this->objectAction->executeAction()['returnValues'];

        MessageQuoteManager::getInstance()->saved();

        if (!$this->draft) {
            FloodControl::getInstance()->registerContent('com.woltlab.wcf.conversation');
            FloodControl::getInstance()->registerContent('com.woltlab.wcf.conversation.message');
        }

        $this->saved();

        // forward
        HeaderUtil::redirect($conversation->getLink());

        exit;
    }

    /**
     * @inheritDoc
     */
    public function readData()
    {
        parent::readData();

        // add breadcrumbs
        PageLocationManager::getInstance()->addParentLocation('com.woltlab.wcf.conversation.ConversationList');
    }

    /**
     * @inheritDoc
     */
    public function assignVariables()
    {
        parent::assignVariables();

        MessageQuoteManager::getInstance()->assignVariables();

        $allowedUserGroupIDs = [];
        foreach (UserGroupCacheBuilder::getInstance()->getData([], 'groups') as $group) {
            if ($group->canBeAddedAsConversationParticipant) {
                $allowedUserGroupIDs[] = $group->groupID;
            }
        }

        WCF::getTPL()->assign([
            'participantCanInvite' => $this->participantCanInvite,
            'participants' => $this->participants,
            'participantsData' => $this->getParticipantsData(),
            'invisibleParticipants' => $this->invisibleParticipants,
            'invisibleParticipantsData' => $this->getParticipantsData(true),
            'action' => 'add',
            'allowedUserGroupIDs' => $allowedUserGroupIDs,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function show()
    {
        if (!WCF::getSession()->getPermission('user.conversation.canStartConversation')) {
            throw new PermissionDeniedException();
        }

        parent::show();
    }

    private function getParticipantsData($invisible = false)
    {
        $result = [];
        $participants = ArrayUtil::trim(\explode(
            ',',
            ($invisible ? $this->invisibleParticipants : $this->participants)
        ));
        foreach ($participants as $username) {
            $result[] = [
                'objectId' => 0,
                'value' => $username,
                'type' => 'user',
            ];
        }

        $participants = ArrayUtil::toIntegerArray(\explode(
            ',',
            ($invisible ? $this->invisibleParticipantsGroupIDs : $this->participantsGroupIDs)
        ));
        foreach ($participants as $groupID) {
            $group = UserGroup::getGroupByID($groupID);
            if (!$group) {
                continue;
            }
            $result[] = [
                'objectId' => $groupID,
                'value' => $group->getName(),
                'type' => 'group',
            ];
        }

        return $result;
    }
}
