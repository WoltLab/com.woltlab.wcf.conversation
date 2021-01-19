<?php

namespace wcf\page;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationAction;
use wcf\data\conversation\ConversationParticipantList;
use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\label\ConversationLabelList;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\message\ViewableConversationMessageList;
use wcf\data\conversation\ViewableConversation;
use wcf\data\modification\log\ConversationLogModificationLogList;
use wcf\data\smiley\SmileyCache;
use wcf\data\user\UserProfile;
use wcf\system\attachment\AttachmentHandler;
use wcf\system\bbcode\BBCodeHandler;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\message\quote\MessageQuoteManager;
use wcf\system\page\PageLocationManager;
use wcf\system\page\ParentPageLocation;
use wcf\system\request\LinkHandler;
use wcf\system\user\signature\SignatureCache;
use wcf\system\WCF;
use wcf\util\HeaderUtil;
use wcf\util\StringUtil;

/**
 * Shows a conversation.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package WoltLabSuite\Core\Page
 *
 * @property    ViewableConversationMessageList $objectList
 */
class ConversationPage extends MultipleLinkPage
{
    /**
     * @inheritDoc
     */
    public $itemsPerPage = CONVERSATION_MESSAGES_PER_PAGE;

    /**
     * @inheritDoc
     */
    public $sortOrder = 'ASC';

    /**
     * @inheritDoc
     */
    public $objectListClassName = ViewableConversationMessageList::class;

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
     * @var ViewableConversation
     */
    public $conversation;

    /**
     * conversation label list
     * @var ConversationLabelList
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

    /**
     * @inheritDoc
     */
    public function readParameters()
    {
        parent::readParameters();

        if (isset($_REQUEST['id'])) {
            $this->conversationID = \intval($_REQUEST['id']);
        }
        if (isset($_REQUEST['messageID'])) {
            $this->messageID = \intval($_REQUEST['messageID']);
        }
        if ($this->messageID) {
            $this->message = new ConversationMessage($this->messageID);
            if (!$this->message->messageID) {
                throw new IllegalLinkException();
            }
            $this->conversationID = $this->message->conversationID;
        }

        $this->conversation = Conversation::getUserConversation($this->conversationID, WCF::getUser()->userID);
        if ($this->conversation === null) {
            throw new IllegalLinkException();
        }
        if (!$this->conversation->canRead()) {
            throw new PermissionDeniedException();
        }

        // load labels
        $this->labelList = ConversationLabel::getLabelsByUser();
        $this->conversation = ViewableConversation::getViewableConversation($this->conversation, $this->labelList);

        // messages per page
        /** @noinspection PhpUndefinedFieldInspection */
        if (WCF::getUser()->conversationMessagesPerPage) {
            /** @noinspection PhpUndefinedFieldInspection */
            $this->itemsPerPage = WCF::getUser()->conversationMessagesPerPage;
        }

        $this->canonicalURL = LinkHandler::getInstance()->getLink('Conversation', [
            'object' => $this->conversation,
        ], ($this->pageNo ? 'pageNo=' . $this->pageNo : ''));
    }

    /**
     * @inheritDoc
     */
    protected function initObjectList()
    {
        parent::initObjectList();

        $this->objectList->getConditionBuilder()
            ->add('conversation_message.conversationID = ?', [$this->conversation->conversationID]);
        $this->objectList->setConversation($this->conversation->getDecoratedObject());

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
        if ($this->action == 'lastPost') {
            $this->goToLastPost();
        }
        if ($this->action == 'firstNew') {
            $this->goToFirstNewPost();
        }
        if ($this->messageID) {
            $this->goToPost();
        }
    }

    /**
     * @inheritDoc
     */
    public function readData()
    {
        parent::readData();

        // add breadcrumbs
        if ($this->conversation->isDraft) {
            // `-1` = pseudo object id to have to pages with identifier `com.woltlab.wcf.conversation.ConversationList`
            PageLocationManager::getInstance()->addParentLocation(
                'com.woltlab.wcf.conversation.ConversationList',
                -1,
                new ParentPageLocation(
                    WCF::getLanguage()->get('wcf.conversation.folder.draft'),
                    LinkHandler::getInstance()->getLink('ConversationList', ['filter' => 'draft'])
                )
            );
        }
        PageLocationManager::getInstance()->addParentLocation('com.woltlab.wcf.conversation.ConversationList');

        // update last visit time count
        if (
            $this->conversation->isNew()
            && (
                $this->objectList->getMaxPostTime() > $this->conversation->lastVisitTime
                || ($this->conversation->joinedAt && !\count($this->objectList))
            )
        ) {
            $visitTime = $this->objectList->getMaxPostTime();
            if ($visitTime == $this->conversation->lastPostTime) {
                $visitTime = TIME_NOW;
            }
            $conversationAction = new ConversationAction(
                [$this->conversation->getDecoratedObject()],
                'markAsRead',
                ['visitTime' => $visitTime]
            );
            $conversationAction->executeAction();
        }

        // get participants
        $this->participantList = new ConversationParticipantList(
            $this->conversationID,
            WCF::getUser()->userID,
            $this->conversation->userID == WCF::getUser()->userID
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
            if ($message->userID) {
                $userIDs[] = $message->userID;
            }
        }

        // fetch special trophies
        if (MODULE_TROPHY) {
            if (!empty($userIDs)) {
                UserProfile::prepareSpecialTrophies(\array_unique($userIDs));
            }
        }

        if (MODULE_USER_SIGNATURE) {
            if (!empty($userIDs)) {
                SignatureCache::getInstance()->cacheUserSignature($userIDs);
            }
        }

        // set attachment permissions
        if ($this->objectList->getAttachmentList() !== null) {
            $this->objectList->getAttachmentList()->setPermissions([
                'canDownload' => true,
                'canViewPreview' => true,
            ]);
        }

        // get timeframe for modifications
        $this->objectList->rewind();
        $startTime = ($this->conversation->joinedAt ?: $this->objectList->current()->time);
        $endTime = ($this->conversation->leftAt ?: TIME_NOW);

        $count = \count($this->objectList);
        if ($count > 1) {
            $this->objectList->seek($count - 1);
            if ($this->objectList->current()->time < $this->conversation->lastPostTime) {
                $sql = "SELECT      time
                        FROM        wcf" . WCF_N . "_conversation_message
                        WHERE       conversationID = ?
                                AND time > ?
                        ORDER BY    time";
                $statement = WCF::getDB()->prepareStatement($sql, 1);
                $statement->execute([$this->conversationID, $this->objectList->current()->time]);
                $endTime = $statement->fetchSingleColumn() - 1;
            }
        }
        $this->objectList->rewind();

        // get invisible participants
        $invisibleParticipantIDs = [];
        if (WCF::getUser()->userID != $this->conversation->userID) {
            foreach ($this->participantList as $participant) {
                if ($participant->isInvisible) {
                    $invisibleParticipantIDs[] = $participant->userID;
                }
            }
        }

        // load modification log entries
        $this->modificationLogList = new ConversationLogModificationLogList($this->conversation->conversationID);
        $this->modificationLogList->getConditionBuilder()
            ->add("modification_log.time BETWEEN ? AND ?", [$startTime, $endTime]);

        if (!empty($invisibleParticipantIDs)) {
            $this->modificationLogList->getConditionBuilder()->add(
                "(modification_log.action <> ? OR modification_log.userID NOT IN (?))",
                ['leave', $invisibleParticipantIDs]
            );
        }

        $this->modificationLogList->readObjects();
    }

    /**
     * @inheritDoc
     */
    public function assignVariables()
    {
        parent::assignVariables();

        MessageQuoteManager::getInstance()->assignVariables();

        $tmpHash = StringUtil::getRandomID();
        $attachmentHandler = new AttachmentHandler('com.woltlab.wcf.conversation.message', 0, $tmpHash, 0);

        WCF::getTPL()->assign([
            'attachmentHandler' => $attachmentHandler,
            'attachmentObjectID' => 0,
            'attachmentObjectType' => 'com.woltlab.wcf.conversation.message',
            'attachmentParentObjectID' => 0,
            'tmpHash' => $tmpHash,
            'attachmentList' => $this->objectList->getAttachmentList(),
            'labelList' => $this->labelList,
            'modificationLogList' => $this->modificationLogList,
            'sortOrder' => $this->sortOrder,
            'conversation' => $this->conversation,
            'conversationID' => $this->conversationID,
            'participants' => $this->participantList->getObjects(),
            'defaultSmilies' => SmileyCache::getInstance()->getCategorySmilies(),
        ]);

        BBCodeHandler::getInstance()->setDisallowedBBCodes(\explode(
            ',',
            WCF::getSession()->getPermission('user.message.disallowedBBCodes')
        ));
    }

    /**
     * Calculates the position of a specific post in this conversation.
     */
    protected function goToPost()
    {
        $conditionBuilder = clone $this->objectList->getConditionBuilder();
        $conditionBuilder->add('time ' . ($this->sortOrder == 'ASC' ? '<=' : '>=') . ' ?', [$this->message->time]);

        $sql = "SELECT  COUNT(*) AS messages
                FROM    wcf" . WCF_N . "_conversation_message conversation_message
                " . $conditionBuilder;
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($conditionBuilder->getParameters());
        $row = $statement->fetchArray();
        $this->pageNo = \intval(\ceil($row['messages'] / $this->itemsPerPage));
    }

    /**
     * Gets the id of the last post in this conversation and forwards the user to this post.
     */
    protected function goToLastPost()
    {
        $sql = "SELECT      conversation_message.messageID
                FROM        wcf" . WCF_N . "_conversation_message conversation_message
                " . $this->objectList->getConditionBuilder() . "
                ORDER BY    time " . ($this->sortOrder == 'ASC' ? 'DESC' : 'ASC');
        $statement = WCF::getDB()->prepareStatement($sql, 1);
        $statement->execute($this->objectList->getConditionBuilder()->getParameters());
        $row = $statement->fetchArray();
        HeaderUtil::redirect(
            LinkHandler::getInstance()->getLink(
                'Conversation',
                [
                    'encodeTitle' => true,
                    'object' => $this->conversation,
                    'messageID' => $row['messageID'],
                ]
            ) . '#message' . $row['messageID']
        );

        exit;
    }

    /**
     * Forwards the user to the first new message in this conversation.
     */
    protected function goToFirstNewPost()
    {
        $conditionBuilder = clone $this->objectList->getConditionBuilder();
        $conditionBuilder->add('time > ?', [$this->conversation->lastVisitTime]);

        $sql = "SELECT      conversation_message.messageID
                FROM        wcf" . WCF_N . "_conversation_message conversation_message
                " . $conditionBuilder . "
                ORDER BY    time ASC";
        $statement = WCF::getDB()->prepareStatement($sql, 1);
        $statement->execute($conditionBuilder->getParameters());
        $row = $statement->fetchArray();
        if ($row !== false) {
            HeaderUtil::redirect(
                LinkHandler::getInstance()->getLink(
                    'Conversation',
                    [
                        'encodeTitle' => true,
                        'object' => $this->conversation,
                        'messageID' => $row['messageID'],
                    ]
                ) . '#message' . $row['messageID']
            );

            exit;
        } else {
            $this->goToLastPost();
        }
    }
}
