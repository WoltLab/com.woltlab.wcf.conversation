<?php

namespace wcf\page;

use Laminas\Diactoros\Response\RedirectResponse;
use wcf\command\conversation\MarkConversationAsRead;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationParticipantList;
use wcf\data\conversation\label\ConversationLabelList;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\message\ConversationMessageList;
use wcf\data\modification\log\ConversationLogModificationLogList;
use wcf\data\smiley\SmileyCache;
use wcf\data\user\UserProfile;
use wcf\system\attachment\AttachmentHandler;
use wcf\system\bbcode\BBCodeHandler;
use wcf\system\cache\runtime\ConversationRuntimeCache;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\interaction\StandaloneInteractionContextMenuComponent;
use wcf\system\interaction\user\ConversationInteractions;
use wcf\system\message\quote\MessageQuoteManager;
use wcf\system\page\PageLocationManager;
use wcf\system\page\ParentPageLocation;
use wcf\system\request\LinkHandler;
use wcf\system\user\signature\SignatureCache;
use wcf\system\WCF;

/**
 * Shows a conversation.
 *
 * @author      Marcel Werk
 * @copyright   2001-2026 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends MultipleLinkPage<ConversationMessageList>
 */
class ConversationPage extends MultipleLinkPage
{
    /**
     * @inheritDoc
     */
    public $itemsPerPage = \CONVERSATION_MESSAGES_PER_PAGE;

    public $sortField = 'conversation_message.time';

    /**
     * @inheritDoc
     */
    public $sortOrder = 'ASC';

    /**
     * @inheritDoc
     */
    public $objectListClassName = ConversationMessageList::class;

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
     * conversation id
     * @var int
     */
    public $conversationID = 0;

    /**
     * viewable conversation object
     * @var Conversation
     */
    public $conversation;

    /**
     * conversation label list
     * @var ConversationLabelList
     * @deprecated 6.2 No longer in use.
     */
    public $labelList;

    /**
     * message id
     * @var int
     */
    public $messageID = 0;

    /**
     * conversation message object
     * @var ConversationMessage
     */
    public $message;

    /**
     * modification log list object
     * @var ConversationLogModificationLogList
     */
    public $modificationLogList;

    /**
     * list of participants
     * @var ConversationParticipantList
     */
    public $participantList;

    #[\Override]
    public function readParameters()
    {
        parent::readParameters();

        if (isset($_REQUEST['id'])) {
            $this->conversationID = \intval($_REQUEST['id']);
        }
        if (isset($_REQUEST['messageID'])) {
            $this->messageID = \intval($_REQUEST['messageID']);
        }
        if ($this->messageID !== 0) {
            $this->message = new ConversationMessage($this->messageID);
            if ($this->message->isNil()) {
                throw new IllegalLinkException();
            }
            $this->conversationID = $this->message->conversationID;
        }

        $this->conversation = ConversationRuntimeCache::getInstance()->getObject($this->conversationID);
        if ($this->conversation === null) {
            throw new IllegalLinkException();
        }
        if (!$this->conversation->canRead()) {
            throw new PermissionDeniedException();
        }

        if ($this->conversation->getFirstMessage() === null) {
            throw new IllegalLinkException();
        }

        // messages per page
        if ((int)WCF::getUser()->conversationMessagesPerPage !== 0) {
            $this->itemsPerPage = WCF::getUser()->conversationMessagesPerPage;
        }

        $this->canonicalURL = LinkHandler::getInstance()->getControllerLink(ConversationPage::class, [
            'object' => $this->conversation,
        ], ($this->pageNo !== 0 ? 'pageNo=' . $this->pageNo : ''));
    }

    #[\Override]
    protected function initObjectList()
    {
        parent::initObjectList();

        $this->objectList->getConditionBuilder()
            ->add('conversation_message.conversationID = ?', [$this->conversation->conversationID]);

        // handle visibility filter
        if ($this->conversation->joinedAt > 0) {
            $this->objectList->getConditionBuilder()
                ->add('conversation_message.time >= ?', [$this->conversation->joinedAt]);
        }
        if ($this->conversation->leftAt > 0) {
            $this->objectList->getConditionBuilder()
                ->add('conversation_message.time <= ?', [$this->conversation->leftAt]);
        }

        // handle jump to
        if ($this->action === 'lastPost') {
            $this->maybeSetPsr7Response(
                $this->goToLastPost()
            );
        }
        if ($this->action === 'firstNew') {
            $this->maybeSetPsr7Response(
                $this->goToFirstNewPost()
            );
        }
        if ($this->messageID !== 0) {
            $this->goToPost();
        }
    }

    #[\Override]
    public function readData()
    {
        parent::readData();

        // add breadcrumbs
        if ($this->conversation->isDraft === 1) {
            // `-1` = pseudo object id to have to pages with identifier `com.woltlab.wcf.conversation.ConversationList`
            PageLocationManager::getInstance()->addParentLocation(
                'com.woltlab.wcf.conversation.ConversationList',
                -1,
                new ParentPageLocation(
                    WCF::getLanguage()->get('wcf.conversation.folder.draft'),
                    LinkHandler::getInstance()->getControllerLink(ConversationListPage::class, ['filter' => 'draft'])
                )
            );
        }
        PageLocationManager::getInstance()->addParentLocation('com.woltlab.wcf.conversation.ConversationList');

        // update last visit time count
        if (
            $this->conversation->isNew()
            && (
                $this->objectList->getMaxTime() > $this->conversation->lastVisitTime
                || ($this->conversation->joinedAt !== null && \count($this->objectList) === 0)
            )
        ) {
            $visitTime = $this->objectList->getMaxTime();
            if ($visitTime === $this->conversation->lastPostTime) {
                $visitTime = \TIME_NOW;
            }
            new MarkConversationAsRead($this->conversation, WCF::getUser(), $visitTime)();
        }

        // get participants
        $this->participantList = new ConversationParticipantList(
            $this->conversationID,
            WCF::getUser()->userID,
            $this->conversation->userID === WCF::getUser()->userID
        );
        $this->participantList->readObjects();

        // init quote objects
        $messageIDs = [];
        foreach ($this->objectList as $message) {
            $messageIDs[] = $message->messageID;
        }
        MessageQuoteManager::getInstance()->initObjects('com.woltlab.wcf.conversation.message', $messageIDs);

        $userIDs = [];
        foreach ($this->objectList as $message) {
            if ($message->userID !== null) {
                $userIDs[] = $message->userID;
            }
        }
        $userIDs = \array_unique($userIDs);

        // fetch special trophies
        if (\MODULE_TROPHY === 1) {
            if ($userIDs !== []) {
                UserProfile::prepareSpecialTrophies($userIDs);
            }
        }

        if (\MODULE_USER_SIGNATURE === 1) {
            if ($userIDs !== []) {
                SignatureCache::getInstance()->cacheUserSignature($userIDs);
            }
        }

        // set attachment permissions
        if ($this->objectList->getAttachments() !== null) {
            $this->objectList->getAttachments()->setPermissions([
                'canDownload' => true,
                'canViewPreview' => true,
            ]);
        }

        // get timeframe for modifications
        $this->objectList->rewind();
        $startTime = ($this->conversation->joinedAt ?: $this->objectList->current()->time);
        $endTime = ($this->conversation->leftAt ?: \TIME_NOW);

        $count = \count($this->objectList);
        if ($count > 1) {
            $this->objectList->seek($count - 1);
            if ($this->objectList->current()->time < $this->conversation->lastPostTime) {
                $sql = "SELECT      time
                        FROM        wcf1_conversation_message
                        WHERE       conversationID = ?
                                AND time > ?
                        ORDER BY    time";
                $statement = WCF::getDB()->prepare($sql, 1);
                $statement->execute([$this->conversationID, $this->objectList->current()->time]);
                $endTime = $statement->fetchSingleColumn() - 1;
            }
        }
        $this->objectList->rewind();

        // get visible participants
        $visibleParticipantIDs = [];
        foreach ($this->participantList as $participant) {
            if ($participant->isInvisible === 0 || WCF::getUser()->userID === $this->conversation->userID) {
                $visibleParticipantIDs[] = $participant->userID;
            }
        }

        // Drafts do not store their participants in conversation_to_user.
        if ($this->conversation->isDraft === 1) {
            $visibleParticipantIDs[] = $this->conversation->userID;
        }

        // load modification log entries
        $this->modificationLogList = new ConversationLogModificationLogList($this->conversation->conversationID);
        $this->modificationLogList->getConditionBuilder()
            ->add("modification_log.time BETWEEN ? AND ?", [$startTime, $endTime]);
        $this->modificationLogList->getConditionBuilder()->add(
            "modification_log.userID IN (?)",
            [$visibleParticipantIDs]
        );

        $this->modificationLogList->readObjects();
    }

    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        MessageQuoteManager::getInstance()->assignVariables();

        $identifier = WCF::getUser()->userID;
        if ($identifier === 0) {
            // Bind the tmpHash to the current session to make it unguessable.
            $identifier = WCF::getSession()->sessionID;
        }

        $tmpHash = \sha1(\implode("\0", [
            // Use class name + conversation ID to match the autosave scoping.
            self::class,
            $this->conversation->conversationID,
            $identifier,
        ]));
        $attachmentHandler = new AttachmentHandler('com.woltlab.wcf.conversation.message', 0, $tmpHash, 0);

        WCF::getTPL()->assign([
            'attachmentHandler' => $attachmentHandler,
            'attachmentObjectID' => 0,
            'attachmentObjectType' => 'com.woltlab.wcf.conversation.message',
            'attachmentParentObjectID' => 0,
            'tmpHash' => $tmpHash,
            'attachmentList' => $this->objectList->getAttachments(),
            'modificationLogList' => $this->modificationLogList,
            'sortOrder' => $this->sortOrder,
            'conversation' => $this->conversation,
            'conversationID' => $this->conversationID,
            'participants' => $this->participantList->getObjects(),
            'defaultSmilies' => SmileyCache::getInstance()->getCategorySmilies(),
            'interactionContextMenu' => StandaloneInteractionContextMenuComponent::forContentInteractionButton(
                new ConversationInteractions(),
                $this->conversation,
                LinkHandler::getInstance()->getControllerLink(ConversationListPage::class),
                WCF::getLanguage()->getDynamicVariable('wcf.conversation.edit.conversation'),
                "core/conversations/{$this->conversationID}/content-header-title"
            ),
        ]);

        BBCodeHandler::getInstance()->setDisallowedBBCodes(\explode(
            ',',
            WCF::getSession()->getPermission('user.message.disallowedBBCodes')
        ));
    }

    /**
     * Calculates the position of a specific post in this conversation.
     *
     * @return void
     */
    protected function goToPost()
    {
        $conditionBuilder = clone $this->objectList->getConditionBuilder();
        $conditionBuilder->add('time ' . ($this->sortOrder === 'ASC' ? '<=' : '>=') . ' ?', [$this->message->time]);

        $sql = "SELECT  COUNT(*) AS messages
                FROM    wcf1_conversation_message conversation_message
                " . $conditionBuilder;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditionBuilder->getParameters());
        $row = $statement->fetchArray();
        $this->pageNo = \intval(\ceil($row['messages'] / $this->itemsPerPage));
    }

    /**
     * Gets the id of the last post in this conversation and forwards the user to this post.
     */
    protected function goToLastPost(): ?RedirectResponse
    {
        $sql = "SELECT      conversation_message.messageID
                FROM        wcf1_conversation_message conversation_message
                " . $this->objectList->getConditionBuilder() . "
                ORDER BY    time " . ($this->sortOrder === 'ASC' ? 'DESC' : 'ASC');
        $statement = WCF::getDB()->prepare($sql, 1);
        $statement->execute($this->objectList->getConditionBuilder()->getParameters());
        $row = $statement->fetchArray();
        if ($row === false) {
            return null;
        }

        return new RedirectResponse(
            LinkHandler::getInstance()->getControllerLink(
                ConversationPage::class,
                [
                    'encodeTitle' => true,
                    'object' => $this->conversation,
                    'messageID' => $row['messageID'],
                ]
            ) . '#message' . $row['messageID']
        );
    }

    /**
     * Forwards the user to the first new message in this conversation.
     */
    protected function goToFirstNewPost(): ?RedirectResponse
    {
        $conditionBuilder = clone $this->objectList->getConditionBuilder();
        $conditionBuilder->add('time > ?', [$this->conversation->lastVisitTime]);

        $sql = "SELECT      conversation_message.messageID
                FROM        wcf1_conversation_message conversation_message
                " . $conditionBuilder . "
                ORDER BY    time ASC";
        $statement = WCF::getDB()->prepare($sql, 1);
        $statement->execute($conditionBuilder->getParameters());
        $row = $statement->fetchArray();
        if ($row === false) {
            return $this->goToLastPost();
        }

        return new RedirectResponse(
            LinkHandler::getInstance()->getControllerLink(
                ConversationPage::class,
                [
                    'encodeTitle' => true,
                    'object' => $this->conversation,
                    'messageID' => $row['messageID'],
                ]
            ) . '#message' . $row['messageID']
        );
    }
}
