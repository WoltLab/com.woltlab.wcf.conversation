<?php

namespace wcf\data\conversation\message;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationAction;
use wcf\data\conversation\ConversationEditor;
use wcf\data\DatabaseObject;
use wcf\data\IAttachmentMessageQuickReplyAction;
use wcf\data\IMessageInlineEditorAction;
use wcf\data\smiley\SmileyCache;
use wcf\event\message\MessageSpamChecking;
use wcf\system\attachment\AttachmentHandler;
use wcf\system\bbcode\BBCodeHandler;
use wcf\system\conversation\ConversationHandler;
use wcf\system\event\EventHandler;
use wcf\system\exception\NamedUserException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\flood\FloodControl;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\html\upcast\HtmlUpcastProcessor;
use wcf\system\message\censorship\Censorship;
use wcf\system\message\embedded\object\MessageEmbeddedObjectManager;
use wcf\system\message\QuickReplyManager;
use wcf\system\message\quote\MessageQuoteManager;
use wcf\system\moderation\queue\ModerationQueueManager;
use wcf\system\search\SearchIndexManager;
use wcf\system\user\notification\object\ConversationMessageUserNotificationObject;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;
use wcf\util\UserUtil;

/**
 * Executes conversation message-related actions.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractDatabaseObjectAction<ConversationMessage, ConversationMessageEditor>
 * @implements IAttachmentMessageQuickReplyAction<Conversation, ConversationMessage, ConversationMessageList>
 */
class ConversationMessageAction extends AbstractDatabaseObjectAction implements
    IAttachmentMessageQuickReplyAction,
    IMessageInlineEditorAction
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

    #[\Override]
    public function create()
    {
        if (!isset($this->parameters['data']['enableHtml'])) {
            $this->parameters['data']['enableHtml'] = 1;
        }

        // count attachments
        if (isset($this->parameters['attachmentHandler'])) {
            $this->parameters['data']['attachments'] = \count($this->parameters['attachmentHandler']);
        }

        if (\LOG_IP_ADDRESS === 1) {
            // add ip address
            if (!isset($this->parameters['data']['ipAddress'])) {
                $this->parameters['data']['ipAddress'] = UserUtil::getIpAddress();
            }
        } else {
            // do not track ip address
            if (isset($this->parameters['data']['ipAddress'])) {
                unset($this->parameters['data']['ipAddress']);
            }
        }

        if (isset($this->parameters['htmlInputProcessor'])) {
            $this->parameters['data']['message'] = $this->parameters['htmlInputProcessor']->getHtml();
        }

        // create message
        $message = parent::create();
        $messageEditor = new ConversationMessageEditor($message);

        // get conversation
        $conversation = ($this->parameters['conversation'] ?? new Conversation($message->conversationID));
        $conversationEditor = new ConversationEditor($conversation);

        if (($this->parameters['isFirstPost'] ?? false) === false) {
            // update last message
            $conversationEditor->addMessage($message);

            $participant = $conversation->getOtherParticipant($message->userID);
            if ($participant !== null && $participant->isInvisible === 1) {
                // make invisible participant visible
                $sql = "UPDATE  wcf1_conversation_to_user
                        SET     isInvisible = 0
                        WHERE   participantID = ?
                            AND conversationID = ?";
                $statement = WCF::getDB()->prepare($sql);
                $statement->execute([$message->userID, $conversation->conversationID]);

                $conversationEditor->updateParticipantCount();
            }

            // reset visibility if it was hidden but not left
            $sql = "UPDATE  wcf1_conversation_to_user
                    SET     hideConversation = ?
                    WHERE   conversationID = ?
                        AND hideConversation = ?";
            $statement = WCF::getDB()->prepare($sql);
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
            (bool)($this->parameters['isFirstPost'] ?? false) ? $conversation->subject : '',
            $message->time,
            $message->userID,
            $message->username
        );

        // update attachments
        if (isset($this->parameters['attachmentHandler'])) {
            $this->parameters['attachmentHandler']->updateObjectID($message->messageID);
        }

        // save embedded objects
        if (isset($this->parameters['htmlInputProcessor'])) {
            $this->parameters['htmlInputProcessor']->setObjectID($message->messageID);

            if (MessageEmbeddedObjectManager::getInstance()->registerObjects($this->parameters['htmlInputProcessor'])) {
                $messageEditor->update(['hasEmbeddedObjects' => 1]);
            }
        }

        // clear quotes
        if (isset($this->parameters['removeQuoteIDs']) && $this->parameters['removeQuoteIDs'] !== []) {
            MessageQuoteManager::getInstance()->markQuotesForRemoval($this->parameters['removeQuoteIDs']);
        }
        MessageQuoteManager::getInstance()->removeMarkedQuotes();

        // fire notification event
        if (($this->parameters['isFirstPost'] ?? false) === false && $conversation->isDraft === 0) {
            // don't notify message author
            $notificationRecipients = \array_diff($conversation->getParticipantIDs(true), [$message->userID]);
            if ($notificationRecipients !== []) {
                UserNotificationHandler::getInstance()->fireEvent(
                    'conversationMessage',
                    'com.woltlab.wcf.conversation.message.notification',
                    new ConversationMessageUserNotificationObject(new ConversationMessage($message->messageID)),
                    $notificationRecipients
                );
            }
        }

        // return new message
        return $message;
    }

    #[\Override]
    public function update()
    {
        // count attachments
        if (isset($this->parameters['attachmentHandler'])) {
            $this->parameters['data']['attachments'] = \count($this->parameters['attachmentHandler']);
        }

        if (isset($this->parameters['htmlInputProcessor'])) {
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
                    $conversation->firstMessageID === $message->messageID ? $conversation->subject : '',
                    $message->time,
                    $message->userID,
                    $message->username
                );

                if (isset($this->parameters['htmlInputProcessor'])) {
                    $this->parameters['htmlInputProcessor']->setObjectID($message->messageID);

                    if ((bool)$message->hasEmbeddedObjects !== MessageEmbeddedObjectManager::getInstance()->registerObjects($this->parameters['htmlInputProcessor'])) {
                        $message->update(['hasEmbeddedObjects' => $message->hasEmbeddedObjects === 1 ? 0 : 1]);
                    }
                }
            }
        }
    }

    #[\Override]
    public function delete()
    {
        $count = parent::delete();

        $attachmentMessageIDs = $conversationIDs = [];
        foreach ($this->getObjects() as $message) {
            if (!\in_array($message->conversationID, $conversationIDs, true)) {
                $conversationIDs[] = $message->conversationID;
            }

            if ($message->attachments !== 0) {
                $attachmentMessageIDs[] = $message->messageID;
            }
        }

        // rebuild conversations
        if ($conversationIDs !== []) {
            $conversationAction = new ConversationAction($conversationIDs, 'rebuild');
            $conversationAction->executeAction();
        }

        if ($this->objectIDs !== []) {
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
        if ($attachmentMessageIDs !== []) {
            AttachmentHandler::removeAttachments('com.woltlab.wcf.conversation.message', $attachmentMessageIDs);
        }

        return $count;
    }

    #[\Override]
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

        if ($this->messageIsProbablySpam()) {
            throw new PermissionDeniedException();
        }
    }

    #[\Override]
    public function quickReply()
    {
        $returnValues = QuickReplyManager::getInstance()->createMessage(
            $this,
            $this->parameters,
            // @phpstan-ignore argument.type
            ConversationAction::class,
            \CONVERSATION_LIST_DEFAULT_SORT_ORDER,
            'conversationMessageList'
        );

        EventHandler::getInstance()->fireAction($this, 'afterQuickReply', $returnValues);

        FloodControl::getInstance()->registerContent('com.woltlab.wcf.conversation.message');

        return $returnValues;
    }

    #[\Override]
    public function validateBeginEdit()
    {
        $this->readInteger('containerID');
        $this->readInteger('objectID');

        $this->conversation = new Conversation($this->parameters['containerID']);
        if ($this->conversation->isNil()) {
            throw new UserInputException('containerID');
        }

        if ($this->conversation->isClosed === 1 || !Conversation::isParticipant([$this->conversation->conversationID])) {
            throw new PermissionDeniedException();
        }

        $this->message = new ConversationMessage($this->parameters['objectID']);
        if ($this->message->isNil()) {
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

    #[\Override]
    public function beginEdit()
    {
        $upcastProcessor = new HtmlUpcastProcessor();
        $upcastProcessor->process(
            $this->message->message,
            'com.woltlab.wcf.conversation.message',
            $this->message->messageID
        );

        $tmpHash = StringUtil::getRandomID();
        $attachmentHandler = new AttachmentHandler(
            'com.woltlab.wcf.conversation.message',
            $this->message->messageID,
            $tmpHash
        );
        $attachmentList = $attachmentHandler->getAttachmentList();

        $tplVariable = [
            'defaultSmilies' => SmileyCache::getInstance()->getCategorySmilies(),
            'message' => $this->message,
            'text' => $upcastProcessor->getHtml(),
            'permissionCanUseSmilies' => 'user.message.canUseSmilies',
            'wysiwygSelector' => 'messageEditor' . $this->message->messageID,
            'attachmentHandler' => $attachmentHandler,
            'attachmentList' => $attachmentList->getObjects(),
            'attachmentObjectID' => $this->message->messageID,
            'attachmentObjectType' => 'com.woltlab.wcf.conversation.message',
            'attachmentParentObjectID' => 0,
            'tmpHash' => $tmpHash,
        ];

        return [
            'actionName' => 'beginEdit',
            'template' => WCF::getTPL()->render('wcf', 'conversationMessageInlineEditor', $tplVariable),
        ];
    }

    #[\Override]
    public function validateSave()
    {
        $this->readString('message', true, 'data');

        if (($this->parameters['data']['message'] ?? '') === '') {
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

        if ($this->messageIsProbablySpam()) {
            throw new PermissionDeniedException();
        }
    }

    #[\Override]
    public function save()
    {
        $data = [];

        if ($this->message->getConversation()->isDraft === 0) {
            $data['lastEditTime'] = \TIME_NOW;
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
        if ($count !== $this->message->attachments) {
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

        $data['attachmentList'] = WCF::getTPL()->render('wcf', 'attachments', [
            'attachmentList' => $attachmentList,
            'objectID' => $this->message->messageID,
        ]);

        return $data;
    }

    #[\Override]
    public function validateContainer(DatabaseObject $container)
    {
        if ($container->conversationID === 0) {
            throw new UserInputException('objectID');
        }
        if ($container->isClosed === 1) {
            throw new PermissionDeniedException();
        }
        if (!$container->canReply()) {
            throw new PermissionDeniedException();
        }
    }

    #[\Override]
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
        if ($disallowedBBCodes !== []) {
            throw new UserInputException(
                'text',
                WCF::getLanguage()->getDynamicVariable(
                    'wcf.message.error.disallowedBBCodes',
                    ['disallowedBBCodes' => $disallowedBBCodes]
                )
            );
        }

        $censoredWords = Censorship::getInstance()->test($message);
        if ($censoredWords !== false) {
            throw new UserInputException(
                'message',
                WCF::getLanguage()->getDynamicVariable(
                    'wcf.message.error.censoredWordsFound',
                    ['censoredWords' => $censoredWords]
                )
            );
        }
    }

    #[\Override]
    public function getMessageList(DatabaseObject $container, int $lastMessageTime)
    {
        $messageList = new ViewableConversationMessageList();
        $messageList->setConversation($container);
        $messageList->getConditionBuilder()
            ->add("conversation_message.conversationID = ?", [$container->conversationID]);
        $messageList->getConditionBuilder()
            ->add("conversation_message.time > ?", [$lastMessageTime]);
        $messageList->sqlOrderBy = "conversation_message.time " . \CONVERSATION_LIST_DEFAULT_SORT_ORDER;
        $messageList->readObjects();

        return $messageList;
    }

    #[\Override]
    public function getPageNo(DatabaseObject $container)
    {
        $sql = "SELECT  COUNT(*) AS count
                FROM    wcf1_conversation_message
                WHERE   conversationID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$container->conversationID]);
        $count = $statement->fetchArray();

        return [\intval(\ceil($count['count'] / \CONVERSATION_MESSAGES_PER_PAGE)), $count['count']];
    }

    #[\Override]
    public function getRedirectUrl(DatabaseObject $container, DatabaseObject $message)
    {
        return $message->getLink();
    }

    #[\Override]
    public function getAttachmentHandler(DatabaseObject $container)
    {
        return new AttachmentHandler('com.woltlab.wcf.conversation.message', 0, $this->parameters['tmpHash']);
    }

    #[\Override]
    public function getHtmlInputProcessor(?string $message = null, int $objectID = 0)
    {
        if ($message === null) {
            return $this->htmlInputProcessor;
        }

        $this->htmlInputProcessor = new HtmlInputProcessor();
        $this->htmlInputProcessor->process($message, 'com.woltlab.wcf.conversation.message', $objectID);

        return $this->htmlInputProcessor;
    }

    /**
     * This method triggers the event for the spam check and returns the result.
     *
     * @since 6.1
     */
    protected function messageIsProbablySpam(): bool
    {
        $event = new MessageSpamChecking(
            $this->htmlInputProcessor,
            !WCF::getUser()->isGuest() ? WCF::getUser() : null,
            UserUtil::getIpAddress()
        );
        EventHandler::getInstance()->fire($event);

        return $event->defaultPrevented();
    }
}
