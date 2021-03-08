<?php

namespace wcf\data\conversation\message;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationAction;
use wcf\data\conversation\ConversationEditor;
use wcf\data\DatabaseObject;
use wcf\data\IAttachmentMessageQuickReplyAction;
use wcf\data\IMessageInlineEditorAction;
use wcf\data\IMessageQuoteAction;
use wcf\data\smiley\SmileyCache;
use wcf\system\attachment\AttachmentHandler;
use wcf\system\bbcode\BBCodeHandler;
use wcf\system\conversation\ConversationHandler;
use wcf\system\exception\NamedUserException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\flood\FloodControl;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\message\censorship\Censorship;
use wcf\system\message\embedded\object\MessageEmbeddedObjectManager;
use wcf\system\message\QuickReplyManager;
use wcf\system\message\quote\MessageQuoteManager;
use wcf\system\moderation\queue\ModerationQueueManager;
use wcf\system\request\LinkHandler;
use wcf\system\search\SearchIndexManager;
use wcf\system\user\notification\object\ConversationMessageUserNotificationObject;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Executes conversation message-related actions.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package WoltLabSuite\Core\Data\Conversation\Message
 *
 * @method  ConversationMessageEditor[] getObjects()
 * @method  ConversationMessageEditor   getSingleObject()
 */
class ConversationMessageAction extends AbstractDatabaseObjectAction implements
    IAttachmentMessageQuickReplyAction,
    IMessageInlineEditorAction,
    IMessageQuoteAction
{
    /**
     * @inheritDoc
     */
    protected $className = ConversationMessageEditor::class;

    /**
     * conversation object
     * @var Conversation
     */
    public $conversation;

    /**
     * @var HtmlInputProcessor
     */
    public $htmlInputProcessor;

    /**
     * conversation message object
     * @var ConversationMessage
     */
    public $message;

    /**
     * @inheritDoc
     * @return  ConversationMessage
     */
    public function create()
    {
        if (!isset($this->parameters['data']['enableHtml'])) {
            $this->parameters['data']['enableHtml'] = 1;
        }

        // count attachments
        if (isset($this->parameters['attachmentHandler']) && $this->parameters['attachmentHandler'] !== null) {
            $this->parameters['data']['attachments'] = \count($this->parameters['attachmentHandler']);
        }

        if (LOG_IP_ADDRESS) {
            // add ip address
            if (!isset($this->parameters['data']['ipAddress'])) {
                $this->parameters['data']['ipAddress'] = WCF::getSession()->ipAddress;
            }
        } else {
            // do not track ip address
            if (isset($this->parameters['data']['ipAddress'])) {
                unset($this->parameters['data']['ipAddress']);
            }
        }

        if (!empty($this->parameters['htmlInputProcessor'])) {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->parameters['data']['message'] = $this->parameters['htmlInputProcessor']->getHtml();
        }

        // create message
        /** @var ConversationMessage $message */
        $message = parent::create();
        $messageEditor = new ConversationMessageEditor($message);

        // get conversation
        $conversation = ($this->parameters['conversation'] ?? new Conversation($message->conversationID));
        $conversationEditor = new ConversationEditor($conversation);

        if (empty($this->parameters['isFirstPost'])) {
            // update last message
            $conversationEditor->addMessage($message);

            // fire notification event
            if (!$conversation->isDraft) {
                // don't notify message author
                $notificationRecipients = \array_diff($conversation->getParticipantIDs(true), [$message->userID]);
                if (!empty($notificationRecipients)) {
                    UserNotificationHandler::getInstance()->fireEvent(
                        'conversationMessage',
                        'com.woltlab.wcf.conversation.message.notification',
                        new ConversationMessageUserNotificationObject($message),
                        $notificationRecipients
                    );
                }
            }

            $userConversation = Conversation::getUserConversation($conversation->conversationID, $message->userID);
            if ($userConversation !== null && $userConversation->isInvisible) {
                // make invisible participant visible
                $sql = "UPDATE  wcf" . WCF_N . "_conversation_to_user
                        SET     isInvisible = 0
                        WHERE   participantID = ?
                            AND conversationID = ?";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute([$message->userID, $conversation->conversationID]);

                $conversationEditor->updateParticipantSummary();
                $conversationEditor->updateParticipantCount();
            }

            // reset visibility if it was hidden but not left
            $sql = "UPDATE  wcf" . WCF_N . "_conversation_to_user
                    SET     hideConversation = ?
                    WHERE   conversationID = ?
                        AND hideConversation = ?";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute([
                Conversation::STATE_DEFAULT,
                $conversation->conversationID,
                Conversation::STATE_HIDDEN,
            ]);
        }

        // reset storage
        UserStorageHandler::getInstance()->reset($conversation->getParticipantIDs(), 'unreadConversationCount');

        // update search index
        SearchIndexManager::getInstance()->set(
            'com.woltlab.wcf.conversation.message',
            $message->messageID,
            $message->message,
            !empty($this->parameters['isFirstPost']) ? $conversation->subject : '',
            $message->time,
            $message->userID,
            $message->username
        );

        // update attachments
        if (isset($this->parameters['attachmentHandler']) && $this->parameters['attachmentHandler'] !== null) {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->parameters['attachmentHandler']->updateObjectID($message->messageID);
        }

        // save embedded objects
        if (!empty($this->parameters['htmlInputProcessor'])) {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->parameters['htmlInputProcessor']->setObjectID($message->messageID);

            if (MessageEmbeddedObjectManager::getInstance()->registerObjects($this->parameters['htmlInputProcessor'])) {
                $messageEditor->update(['hasEmbeddedObjects' => 1]);
            }
        }

        // clear quotes
        if (isset($this->parameters['removeQuoteIDs']) && !empty($this->parameters['removeQuoteIDs'])) {
            MessageQuoteManager::getInstance()->markQuotesForRemoval($this->parameters['removeQuoteIDs']);
        }
        MessageQuoteManager::getInstance()->removeMarkedQuotes();

        // return new message
        return $message;
    }

    /**
     * @inheritDoc
     */
    public function update()
    {
        // count attachments
        if (isset($this->parameters['attachmentHandler']) && $this->parameters['attachmentHandler'] !== null) {
            $this->parameters['data']['attachments'] = \count($this->parameters['attachmentHandler']);
        }

        if (!empty($this->parameters['htmlInputProcessor'])) {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->parameters['data']['message'] = $this->parameters['htmlInputProcessor']->getHtml();
        }

        parent::update();

        // update search index / embedded objects
        if (isset($this->parameters['data']) && isset($this->parameters['data']['message'])) {
            foreach ($this->getObjects() as $message) {
                $conversation = $message->getConversation();
                SearchIndexManager::getInstance()->set(
                    'com.woltlab.wcf.conversation.message',
                    $message->messageID,
                    $this->parameters['data']['message'],
                    $conversation->firstMessageID == $message->messageID ? $conversation->subject : '',
                    $message->time,
                    $message->userID,
                    $message->username
                );

                if (!empty($this->parameters['htmlInputProcessor'])) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    $this->parameters['htmlInputProcessor']->setObjectID($message->messageID);

                    if ($message->hasEmbeddedObjects != MessageEmbeddedObjectManager::getInstance()->registerObjects($this->parameters['htmlInputProcessor'])) {
                        $message->update(['hasEmbeddedObjects' => $message->hasEmbeddedObjects ? 0 : 1]);
                    }
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function delete()
    {
        $count = parent::delete();

        $attachmentMessageIDs = $conversationIDs = [];
        foreach ($this->getObjects() as $message) {
            if (!\in_array($message->conversationID, $conversationIDs)) {
                $conversationIDs[] = $message->conversationID;
            }

            if ($message->attachments) {
                $attachmentMessageIDs[] = $message->messageID;
            }
        }

        // rebuild conversations
        if (!empty($conversationIDs)) {
            $conversationAction = new ConversationAction($conversationIDs, 'rebuild');
            $conversationAction->executeAction();
        }

        if (!empty($this->objectIDs)) {
            // delete notifications
            UserNotificationHandler::getInstance()
                ->removeNotifications('com.woltlab.wcf.conversation.message.notification', $this->objectIDs);

            // update search index
            SearchIndexManager::getInstance()->delete('com.woltlab.wcf.conversation.message', $this->objectIDs);

            // update embedded objects
            MessageEmbeddedObjectManager::getInstance()
                ->removeObjects('com.woltlab.wcf.conversation.message', $this->objectIDs);

            // remove moderation queues
            ModerationQueueManager::getInstance()
                ->removeQueues('com.woltlab.wcf.conversation.message', $this->objectIDs);
        }

        // remove attachments
        if (!empty($attachmentMessageIDs)) {
            AttachmentHandler::removeAttachments('com.woltlab.wcf.conversation.message', $attachmentMessageIDs);
        }

        return $count;
    }

    /**
     * @inheritDoc
     */
    public function validateQuickReply()
    {
        try {
            ConversationHandler::getInstance()->enforceFloodControl(true);
        } catch (NamedUserException $e) {
            throw new UserInputException('message', $e->getMessage());
        }

        QuickReplyManager::getInstance()->setDisallowedBBCodes(\explode(
            ',',
            WCF::getSession()->getPermission('user.message.disallowedBBCodes')
        ));
        QuickReplyManager::getInstance()->validateParameters($this, $this->parameters, Conversation::class);
    }

    /**
     * @inheritDoc
     */
    public function quickReply()
    {
        $returnValues = QuickReplyManager::getInstance()->createMessage(
            $this,
            $this->parameters,
            ConversationAction::class,
            CONVERSATION_LIST_DEFAULT_SORT_ORDER,
            'conversationMessageList'
        );

        FloodControl::getInstance()->registerContent('com.woltlab.wcf.conversation.message');

        return $returnValues;
    }

    /**
     * @inheritDoc
     */
    public function validateJumpToExtended()
    {
        $this->readInteger('containerID');
        $this->readString('message', true);
        $this->readString('tmpHash', true);

        $this->conversation = new Conversation($this->parameters['containerID']);
        if (!$this->conversation->conversationID) {
            throw new UserInputException('containerID');
        } elseif (
            $this->conversation->isClosed
            || !Conversation::isParticipant([$this->conversation->conversationID])
        ) {
            throw new PermissionDeniedException();
        }

        // editing existing message
        if (isset($this->parameters['messageID'])) {
            $this->message = new ConversationMessage(\intval($this->parameters['messageID']));
            if (!$this->message->messageID || ($this->message->conversationID != $this->conversation->conversationID)) {
                throw new UserInputException('messageID');
            }

            if (!$this->message->canEdit()) {
                throw new PermissionDeniedException();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function jumpToExtended()
    {
        // quick reply
        if ($this->message === null) {
            QuickReplyManager::getInstance()
                ->setMessage('conversation', $this->conversation->conversationID, $this->parameters['message']);
            $url = LinkHandler::getInstance()->getLink(
                'ConversationMessageAdd',
                ['id' => $this->conversation->conversationID]
            );
        } else {
            // editing message
            QuickReplyManager::getInstance()
                ->setMessage('conversationMessage', $this->message->messageID, $this->parameters['message']);
            $url = LinkHandler::getInstance()->getLink(
                'ConversationMessageEdit',
                ['id' => $this->message->messageID]
            );
        }

        if (!empty($this->parameters['tmpHash'])) {
            QuickReplyManager::getInstance()->setTmpHash($this->parameters['tmpHash']);
        }

        // redirect
        return [
            'url' => $url,
        ];
    }

    /**
     * @inheritDoc
     */
    public function validateBeginEdit()
    {
        $this->readInteger('containerID');
        $this->readInteger('objectID');

        $this->conversation = new Conversation($this->parameters['containerID']);
        if (!$this->conversation->conversationID) {
            throw new UserInputException('containerID');
        }

        if ($this->conversation->isClosed || !Conversation::isParticipant([$this->conversation->conversationID])) {
            throw new PermissionDeniedException();
        }

        $this->message = new ConversationMessage($this->parameters['objectID']);
        if (!$this->message->messageID) {
            throw new UserInputException('objectID');
        }

        if (!$this->message->canEdit()) {
            throw new PermissionDeniedException();
        }

        BBCodeHandler::getInstance()->setDisallowedBBCodes(\explode(
            ',',
            WCF::getSession()->getPermission('user.message.disallowedBBCodes')
        ));
    }

    /**
     * @inheritDoc
     */
    public function beginEdit()
    {
        WCF::getTPL()->assign([
            'defaultSmilies' => SmileyCache::getInstance()->getCategorySmilies(),
            'message' => $this->message,
            'permissionCanUseSmilies' => 'user.message.canUseSmilies',
            'wysiwygSelector' => 'messageEditor' . $this->message->messageID,
        ]);

        $tmpHash = StringUtil::getRandomID();
        $attachmentHandler = new AttachmentHandler(
            'com.woltlab.wcf.conversation.message',
            $this->message->messageID,
            $tmpHash
        );
        $attachmentList = $attachmentHandler->getAttachmentList();

        WCF::getTPL()->assign([
            'attachmentHandler' => $attachmentHandler,
            'attachmentList' => $attachmentList->getObjects(),
            'attachmentObjectID' => $this->message->messageID,
            'attachmentObjectType' => 'com.woltlab.wcf.conversation.message',
            'attachmentParentObjectID' => 0,
            'tmpHash' => $tmpHash,
        ]);

        return [
            'actionName' => 'beginEdit',
            'template' => WCF::getTPL()->fetch('conversationMessageInlineEditor'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function validateSave()
    {
        $this->readString('message', true, 'data');

        if (empty($this->parameters['data']['message'])) {
            throw new UserInputException(
                'message',
                WCF::getLanguage()->getDynamicVariable('wcf.global.form.error.empty')
            );
        }

        $this->validateBeginEdit();

        $this->validateMessage(
            $this->conversation,
            $this->getHtmlInputProcessor($this->parameters['data']['message'], $this->message->messageID)
        );
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        $data = [];

        if (!$this->message->getConversation()->isDraft) {
            $data['lastEditTime'] = TIME_NOW;
            $data['editCount'] = $this->message->editCount + 1;
        }
        // execute update action
        $action = new self([$this->message], 'update', [
            'data' => $data,
            'htmlInputProcessor' => $this->getHtmlInputProcessor(),
        ]);
        $action->executeAction();

        // load new message
        $this->message = new ConversationMessage($this->message->messageID);
        $this->message->getAttachments();

        $attachmentList = $this->message->getAttachments(true);
        $count = 0;
        if ($attachmentList !== null) {
            // set permissions
            $attachmentList->setPermissions([
                'canDownload' => true,
                'canViewPreview' => true,
            ]);

            $count = \count($attachmentList);
        }

        // update count to reflect number of attachments after edit
        if ($count != $this->message->attachments) {
            $messageEditor = new ConversationMessageEditor($this->message);
            $messageEditor->update(['attachments' => $count]);
        }

        // load embedded objects
        MessageEmbeddedObjectManager::getInstance()
            ->loadObjects('com.woltlab.wcf.conversation.message', [$this->message->messageID]);

        $data = [
            'actionName' => 'save',
            'message' => $this->message->getFormattedMessage(),
        ];

        WCF::getTPL()->assign([
            'attachmentList' => $attachmentList,
            'objectID' => $this->message->messageID,
        ]);
        $data['attachmentList'] = WCF::getTPL()->fetch('attachments');

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function validateContainer(DatabaseObject $conversation)
    {
        /** @var Conversation $conversation */

        if (!$conversation->conversationID) {
            throw new UserInputException('objectID');
        }
        if ($conversation->isClosed) {
            throw new PermissionDeniedException();
        }
        $conversation->loadUserParticipation();
        if (!$conversation->canRead() || !$conversation->canReply()) {
            throw new PermissionDeniedException();
        }
    }

    /**
     * @inheritDoc
     */
    public function validateMessage(DatabaseObject $container, HtmlInputProcessor $htmlInputProcessor)
    {
        $message = $htmlInputProcessor->getTextContent();
        if (\mb_strlen($message) > WCF::getSession()->getPermission('user.conversation.maxLength')) {
            throw new UserInputException(
                'message',
                WCF::getLanguage()->getDynamicVariable(
                    'wcf.message.error.tooLong',
                    ['maxTextLength' => WCF::getSession()->getPermission('user.conversation.maxLength')]
                )
            );
        }

        // search for disallowed bbcodes
        $disallowedBBCodes = $htmlInputProcessor->validate();
        if (!empty($disallowedBBCodes)) {
            throw new UserInputException(
                'text',
                WCF::getLanguage()->getDynamicVariable(
                    'wcf.message.error.disallowedBBCodes',
                    ['disallowedBBCodes' => $disallowedBBCodes]
                )
            );
        }

        // search for censored words
        if (ENABLE_CENSORSHIP) {
            $result = Censorship::getInstance()->test($message);
            if ($result) {
                throw new UserInputException(
                    'message',
                    WCF::getLanguage()->getDynamicVariable(
                        'wcf.message.error.censoredWordsFound',
                        ['censoredWords' => $result]
                    )
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getMessageList(DatabaseObject $conversation, $lastMessageTime)
    {
        /** @var Conversation $conversation */

        $messageList = new ViewableConversationMessageList();
        $messageList->setConversation($conversation);
        $messageList->getConditionBuilder()
            ->add("conversation_message.conversationID = ?", [$conversation->conversationID]);
        $messageList->getConditionBuilder()
            ->add("conversation_message.time > ?", [$lastMessageTime]);
        $messageList->sqlOrderBy = "conversation_message.time " . CONVERSATION_LIST_DEFAULT_SORT_ORDER;
        $messageList->readObjects();

        return $messageList;
    }

    /**
     * @inheritDoc
     */
    public function getPageNo(DatabaseObject $conversation)
    {
        /** @var Conversation $conversation */

        $sql = "SELECT  COUNT(*) AS count
                FROM    wcf" . WCF_N . "_conversation_message
                WHERE   conversationID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$conversation->conversationID]);
        $count = $statement->fetchArray();

        return [\intval(\ceil($count['count'] / CONVERSATION_MESSAGES_PER_PAGE)), $count['count']];
    }

    /**
     * @inheritDoc
     */
    public function getRedirectUrl(DatabaseObject $conversation, DatabaseObject $message)
    {
        /** @var ConversationMessage $message */
        return $message->getLink();
    }

    /**
     * @inheritDoc
     */
    public function validateSaveFullQuote()
    {
        $this->message = $this->getSingleObject();

        if (!Conversation::isParticipant([$this->message->conversationID])) {
            throw new PermissionDeniedException();
        }
    }

    /**
     * @inheritDoc
     */
    public function saveFullQuote()
    {
        $quoteID = MessageQuoteManager::getInstance()->addQuote(
            'com.woltlab.wcf.conversation.message',
            $this->message->conversationID,
            $this->message->messageID,
            $this->message->getExcerpt(),
            $this->message->getMessage()
        );

        if ($quoteID === false) {
            $removeQuoteID = MessageQuoteManager::getInstance()->getQuoteID(
                'com.woltlab.wcf.conversation.message',
                $this->message->messageID,
                $this->message->getExcerpt(),
                $this->message->getMessage()
            );
            MessageQuoteManager::getInstance()->removeQuote($removeQuoteID);
        }

        $returnValues = [
            'count' => MessageQuoteManager::getInstance()->countQuotes(),
            'fullQuoteMessageIDs' => MessageQuoteManager::getInstance()->getFullQuoteObjectIDs(
                ['com.woltlab.wcf.conversation.message']
            ),
        ];

        if ($quoteID) {
            $returnValues['renderedQuote'] = MessageQuoteManager::getInstance()->getQuoteComponents($quoteID);
        }

        return $returnValues;
    }

    /**
     * @inheritDoc
     */
    public function validateSaveQuote()
    {
        $this->readString('message');
        $this->readBoolean('renderQuote', true);
        $this->message = $this->getSingleObject();

        if (!Conversation::isParticipant([$this->message->conversationID])) {
            throw new PermissionDeniedException();
        }
    }

    /**
     * @inheritDoc
     */
    public function saveQuote()
    {
        $quoteID = MessageQuoteManager::getInstance()->addQuote(
            'com.woltlab.wcf.conversation.message',
            $this->message->conversationID,
            $this->message->messageID,
            $this->parameters['message'],
            false
        );

        $returnValues = [
            'count' => MessageQuoteManager::getInstance()->countQuotes(),
            'fullQuoteMessageIDs' => MessageQuoteManager::getInstance()->getFullQuoteObjectIDs(
                ['com.woltlab.wcf.conversation.message']
            ),
        ];

        if ($this->parameters['renderQuote']) {
            $returnValues['renderedQuote'] = MessageQuoteManager::getInstance()->getQuoteComponents($quoteID);
        }

        return $returnValues;
    }

    /**
     * @inheritDoc
     */
    public function validateGetRenderedQuotes()
    {
        $this->readInteger('parentObjectID');

        $this->conversation = new Conversation($this->parameters['parentObjectID']);
        if (!$this->conversation->conversationID) {
            throw new UserInputException('parentObjectID');
        }
    }

    /**
     * @inheritDoc
     */
    public function getRenderedQuotes()
    {
        $quotes = MessageQuoteManager::getInstance()
            ->getQuotesByParentObjectID('com.woltlab.wcf.conversation.message', $this->conversation->conversationID);

        return [
            'template' => \implode("\n\n", $quotes),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAttachmentHandler(DatabaseObject $conversation)
    {
        return new AttachmentHandler('com.woltlab.wcf.conversation.message', 0, $this->parameters['tmpHash']);
    }

    /**
     * @inheritDoc
     */
    public function getHtmlInputProcessor($message = null, $objectID = 0)
    {
        if ($message === null) {
            return $this->htmlInputProcessor;
        }

        $this->htmlInputProcessor = new HtmlInputProcessor();
        $this->htmlInputProcessor->process($message, 'com.woltlab.wcf.conversation.message', $objectID);

        return $this->htmlInputProcessor;
    }
}
