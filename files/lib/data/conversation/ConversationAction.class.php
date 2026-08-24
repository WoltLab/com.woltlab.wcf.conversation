<?php

namespace wcf\data\conversation;

use wcf\command\conversation\MarkAllConversationsAsRead;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\conversation\message\ConversationMessageAction;
use wcf\data\conversation\message\ConversationMessageList;
use wcf\data\IVisitableObjectAction;
use wcf\data\user\UserProfile;
use wcf\page\ConversationPage;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\conversation\ConversationHandler;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\log\modification\ConversationModificationLogHandler;
use wcf\system\request\LinkHandler;
use wcf\system\style\FontAwesomeIcon;
use wcf\system\user\notification\object\ConversationUserNotificationObject;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Executes conversation-related actions.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractDatabaseObjectAction<Conversation, ConversationEditor>
 */
class ConversationAction extends AbstractDatabaseObjectAction implements IVisitableObjectAction
{
    /**
     * @inheritDoc
     */
    protected $className = ConversationEditor::class;

    /**
     * conversation object
     * @var ConversationEditor
     */
    public $conversation;

    /**
     * @inheritDoc
     */
    public function validateAction()
    {
        // Only `create`, `update` and `delete` consult the `$permissions*` properties,
        // every other action has to be guarded here.
        if (\MODULE_CONVERSATION === 0) {
            throw new IllegalLinkException();
        }

        if (!WCF::getSession()->getPermission('user.conversation.canUseConversation')) {
            throw new PermissionDeniedException();
        }

        parent::validateAction();
    }

    /**
     * @inheritDoc
     */
    public function create()
    {
        // create conversation
        $data = $this->parameters['data'];
        $data['lastPosterID'] = $data['userID'];
        $data['lastPoster'] = $data['username'];
        $data['lastPostTime'] = $data['time'];
        // count participants
        if (!empty($this->parameters['participants'])) {
            $data['participants'] = \count($this->parameters['participants']);
        }
        // count attachments
        if (isset($this->parameters['message_attachmentHandler'])) {
            $data['attachments'] = \count($this->parameters['message_attachmentHandler']);
        }
        $conversation = \call_user_func([$this->className, 'create'], $data);
        $conversationEditor = new ConversationEditor($conversation);

        if (!$conversation->isDraft) {
            // save participants
            $conversationEditor->updateParticipants(
                (!empty($this->parameters['participants']) ? $this->parameters['participants'] : []),
                (!empty($this->parameters['invisibleParticipants']) ? $this->parameters['invisibleParticipants'] : []),
                'all'
            );

            // add author
            if ($data['userID'] !== null) {
                $conversationEditor->updateParticipants([$data['userID']], [], 'all');
            }

            // update conversation count
            UserStorageHandler::getInstance()->reset($conversation->getParticipantIDs(), 'conversationCount');

            // mark conversation as read for the author
            $sql = "UPDATE  wcf1_conversation_to_user
                    SET     lastVisitTime = ?
                    WHERE   participantID = ?
                        AND conversationID = ?";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute([$data['time'], $data['userID'], $conversation->conversationID]);
        } else {
            // update conversation count
            UserStorageHandler::getInstance()->reset([$data['userID']], 'conversationCount');
        }

        // create message
        $messageData = $this->parameters['messageData'] ?? [];
        $messageData['conversationID'] = $conversation->conversationID;
        $messageData['time'] = $this->parameters['data']['time'];
        $messageData['userID'] = $this->parameters['data']['userID'];
        $messageData['username'] = $this->parameters['data']['username'];

        $messageAction = new ConversationMessageAction([], 'create', [
            'data' => $messageData,
            'conversation' => $conversation,
            'isFirstPost' => true,
            'attachmentHandler' => $this->parameters['message_attachmentHandler'] ?? null,
            'htmlInputProcessor' => $this->parameters['message_htmlInputProcessor'] ?? null,
        ]);
        $resultValues = $messageAction->executeAction();

        // update first message id
        $conversationEditor->update([
            'firstMessageID' => $resultValues['returnValues']->messageID,
        ]);

        if (!$conversation->isDraft) {
            // fire notification event
            $notificationRecipients = \array_merge(
                !empty($this->parameters['participants']) ? $this->parameters['participants'] : [],
                !empty($this->parameters['invisibleParticipants']) ? $this->parameters['invisibleParticipants'] : []
            );
            UserNotificationHandler::getInstance()->fireEvent(
                'conversation',
                'com.woltlab.wcf.conversation.notification',
                new ConversationUserNotificationObject(new Conversation($conversation->conversationID)),
                $notificationRecipients
            );
        }

        // Reload the object so that `firstMessageID` is set.
        return new Conversation($conversation->conversationID);
    }

    /**
     * @inheritDoc
     */
    public function delete()
    {
        // deletes messages
        $messageList = new ConversationMessageList();
        $messageList->getConditionBuilder()->add('conversation_message.conversationID IN (?)', [$this->objectIDs]);
        $messageList->readObjectIDs();
        $action = new ConversationMessageAction($messageList->getObjectIDs(), 'delete');
        $action->executeAction();

        // get the list of participants in order to reset the 'unread conversation'-counter
        $participantIDs = [];
        if (!empty($this->objectIDs)) {
            $conditions = new PreparedStatementConditionBuilder();
            $conditions->add("conversationID IN (?)", [$this->objectIDs]);
            $sql = "SELECT  DISTINCT participantID
                    FROM    wcf1_conversation_to_user
                    " . $conditions;
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute($conditions->getParameters());

            while ($participantID = $statement->fetchColumn()) {
                $participantIDs[] = $participantID;
            }
        }

        // delete conversations
        $count = parent::delete();

        if (!empty($this->objectIDs)) {
            // delete notifications
            UserNotificationHandler::getInstance()
                ->removeNotifications('com.woltlab.wcf.conversation.notification', $this->objectIDs);

            // remove modification logs
            ConversationModificationLogHandler::getInstance()->deleteLogs($this->objectIDs);

            // reset the number of unread conversations
            if (!empty($participantIDs)) {
                UserStorageHandler::getInstance()->reset($participantIDs, 'unreadConversationCount');
            }
        }

        return $count;
    }

    /**
     * @inheritDoc
     */
    public function update()
    {
        if (!isset($this->parameters['participants'])) {
            $this->parameters['participants'] = [];
        }
        if (!isset($this->parameters['invisibleParticipants'])) {
            $this->parameters['invisibleParticipants'] = [];
        }

        // count participants
        if (!empty($this->parameters['participants'])) {
            $this->parameters['data']['participants'] = \count($this->parameters['participants']);
        }

        parent::update();

        if (isset($this->parameters['messageData'])) {
            $messageIDs = [];
            foreach ($this->getObjects() as $conversation) {
                $messageIDs[] = $conversation->getFirstMessage()->messageID;
            }

            if ($messageIDs !== []) {
                (new ConversationMessageAction(
                    $messageIDs,
                    'update',
                    $this->parameters['messageData']
                ))->executeAction();
            }
        }

        foreach ($this->getObjects() as $conversation) {
            // participants
            if (!empty($this->parameters['participants']) || !empty($this->parameters['invisibleParticipants'])) {
                // get current participants
                $participantIDs = $conversation->getParticipantIDs();

                $conversation->updateParticipants(
                    (!empty($this->parameters['participants']) ? $this->parameters['participants'] : []),
                    (!empty($this->parameters['invisibleParticipants']) ? $this->parameters['invisibleParticipants'] : []),
                    (!empty($this->parameters['visibility']) ? $this->parameters['visibility'] : 'all')
                );

                // check if new participants have been added
                $newParticipantIDs = \array_diff(\array_merge(
                    $this->parameters['participants'],
                    $this->parameters['invisibleParticipants']
                ), $participantIDs);
                if (!empty($newParticipantIDs)) {
                    // update conversation count
                    UserStorageHandler::getInstance()->reset($newParticipantIDs, 'unreadConversationCount');
                    UserStorageHandler::getInstance()->reset($newParticipantIDs, 'conversationCount');

                    // fire notification event
                    UserNotificationHandler::getInstance()->fireEvent(
                        'conversation',
                        'com.woltlab.wcf.conversation.notification',
                        new ConversationUserNotificationObject($conversation->getDecoratedObject()),
                        $newParticipantIDs
                    );
                }
            }

            // draft status
            if (isset($this->parameters['data']['isDraft'])) {
                if ($conversation->isDraft && !$this->parameters['data']['isDraft']) {
                    // add author
                    $conversation->updateParticipants([$conversation->userID], [], 'all');

                    // update conversation count
                    UserStorageHandler::getInstance()
                        ->reset($conversation->getParticipantIDs(), 'unreadConversationCount');
                    UserStorageHandler::getInstance()
                        ->reset($conversation->getParticipantIDs(), 'conversationCount');
                }
            }
        }
    }

    /**
     * @inheritDoc
     * @deprecated 6.2 Use `MarkConversationAsRead` instead.
     */
    public function markAsRead()
    {
        if (empty($this->parameters['visitTime'])) {
            $this->parameters['visitTime'] = TIME_NOW;
        }

        // in case this is a call via PHP and the userID parameter is missing, set it to the userID of the current user
        if (!isset($this->parameters['userID'])) {
            $this->parameters['userID'] = WCF::getUser()->userID;
        }

        if (empty($this->objects)) {
            $this->readObjects();
        }

        $conversationIDs = [];
        $sql = "UPDATE  wcf1_conversation_to_user
                SET     lastVisitTime = ?
                WHERE   participantID = ?
                    AND conversationID = ?";
        $statement = WCF::getDB()->prepare($sql);
        WCF::getDB()->beginTransaction();
        foreach ($this->getObjects() as $conversation) {
            $statement->execute([
                $this->parameters['visitTime'],
                $this->parameters['userID'],
                $conversation->conversationID,
            ]);
            $conversationIDs[] = $conversation->conversationID;
        }
        WCF::getDB()->commitTransaction();

        // reset storage
        UserStorageHandler::getInstance()->reset([$this->parameters['userID']], 'unreadConversationCount');

        // mark notifications as confirmed
        if (!empty($conversationIDs)) {
            // 1) Mark notifications about new conversations as read.
            UserNotificationHandler::getInstance()->markAsConfirmed(
                'conversation',
                'com.woltlab.wcf.conversation.notification',
                [$this->parameters['userID']],
                $conversationIDs
            );

            // 2) Mark notifications about new replies as read.
            $eventID = UserNotificationHandler::getInstance()
                ->getEvent('com.woltlab.wcf.conversation.message.notification', 'conversationMessage')
                ->eventID;

            $condition = new PreparedStatementConditionBuilder();
            $condition->add('notification.userID = ?', [$this->parameters['userID']]);
            $condition->add('notification.confirmTime = ?', [0]);
            $condition->add('notification.eventID = ?', [$eventID]);
            $condition->add("notification.objectID IN (
                SELECT  messageID
                FROM    wcf1_conversation_message
                WHERE   conversationID IN (?)
                    AND time <= ?
            )", [
                $conversationIDs,
                $this->parameters['visitTime'],
            ]);

            $sql = "SELECT  notificationID
                    FROM    wcf1_user_notification notification
                    {$condition}";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute($condition->getParameters());

            UserNotificationHandler::getInstance()->markAsConfirmedByIDs(
                $statement->fetchAll(\PDO::FETCH_COLUMN)
            );
        }

        $returnValues = [
            'totalCount' => ConversationHandler::getInstance()
                ->getUnreadConversationCount($this->parameters['userID'], true),
        ];

        if (\count($conversationIDs) == 1) {
            $returnValues['markAsRead'] = \reset($conversationIDs);
        }

        // @phpstan-ignore return.void
        return $returnValues;
    }

    /**
     * @inheritDoc
     * @deprecated 6.2 Use `MarkConversationAsRead` instead.
     */
    public function validateMarkAsRead()
    {
        // visitTime might not be in the future
        if (isset($this->parameters['visitTime'])) {
            $this->parameters['visitTime'] = \intval($this->parameters['visitTime']);
            if ($this->parameters['visitTime'] > TIME_NOW) {
                $this->parameters['visitTime'] = TIME_NOW;
            }
        }

        // userID should always be equal to the userID of the current user when called via AJAX
        $this->parameters['userID'] = WCF::getUser()->userID;

        if (empty($this->objects)) {
            $this->readObjects();
        }

        // check participation
        $conversationIDs = [];
        foreach ($this->getObjects() as $conversation) {
            $conversationIDs[] = $conversation->conversationID;
        }

        if (empty($conversationIDs)) {
            throw new UserInputException('objectIDs');
        }

        if (!Conversation::isParticipant($conversationIDs)) {
            throw new PermissionDeniedException();
        }
    }

    /**
     * Marks all conversations as read.
     *
     * @return array{markAllAsRead: bool}
     * @deprecated 6.2 Use `MarkAllConversationsAsRead` instead.
     */
    public function markAllAsRead()
    {
        (new MarkAllConversationsAsRead(WCF::getUser()))();

        return [
            'markAllAsRead' => true,
        ];
    }

    /**
     * Validates the markAllAsRead action.
     *
     * @return void
     * @deprecated 6.2 Use `MarkAllConversationsAsRead` instead.
     */
    public function validateMarkAllAsRead()
    {
        // does nothing
    }

    /**
     * @since 5.5
     */
    public function validateGetConversations(): void
    {
        if (!\MODULE_CONVERSATION) {
            throw new IllegalLinkException();
        }

        if (!WCF::getSession()->getPermission('user.conversation.canUseConversation')) {
            throw new PermissionDeniedException();
        }
    }

    /**
     * @return array{
     *  items: array<int, array{
     *      content: string,
     *      image: string,
     *      isUnread: bool,
     *      link: string,
     *      objectId: int,
     *      time: int,
     *      usernames: string[],
     *  }>,
     *  totalCount: int,
     * }
     * @since 5.5
     */
    public function getConversations(): array
    {
        $sqlSelect = '  , (
            SELECT      participantID
            FROM        wcf1_conversation_to_user
            WHERE       conversationID = conversation.conversationID
                    AND participantID <> conversation.userID
                    AND isInvisible = 0
            ORDER BY    username, participantID
            LIMIT 1
        ) AS otherParticipantID
        , (
            SELECT      username
            FROM        wcf1_conversation_to_user
            WHERE       conversationID = conversation.conversationID
                    AND participantID <> conversation.userID
                    AND isInvisible = 0
            ORDER BY    username, participantID
            LIMIT       1
        ) AS otherParticipant';

        $unreadConversationList = new UserConversationList(WCF::getUser()->userID);
        $unreadConversationList->sqlSelects .= $sqlSelect;
        $unreadConversationList->getConditionBuilder()->add('conversation_to_user.lastVisitTime < lastPostTime');
        $unreadConversationList->sqlLimit = 10;
        $unreadConversationList->sqlOrderBy = 'lastPostTime DESC';
        $unreadConversationList->readObjects();

        $conversations = [];
        $count = 0;
        foreach ($unreadConversationList as $conversation) {
            $conversations[] = $conversation;
            $count++;
        }

        if ($count < 10) {
            $conversationList = new UserConversationList(WCF::getUser()->userID);
            $conversationList->sqlSelects .= $sqlSelect;
            $conversationList->getConditionBuilder()->add('conversation_to_user.lastVisitTime >= lastPostTime');
            $conversationList->sqlLimit = (10 - $count);
            $conversationList->sqlOrderBy = 'lastPostTime DESC';
            $conversationList->readObjects();

            foreach ($conversationList as $conversation) {
                $conversations[] = $conversation;
            }
        }

        $totalCount = ConversationHandler::getInstance()->getUnreadConversationCount();
        if ($count < 10 && $count < $totalCount) {
            UserStorageHandler::getInstance()->reset([WCF::getUser()->userID], 'unreadConversationCount');
        }

        foreach ($unreadConversationList->getObjects() as $conversation) {
            // @phpstan-ignore property.notFound
            if ($conversation->otherParticipantID) {
                UserProfileRuntimeCache::getInstance()->cacheObjectID($conversation->otherParticipantID);
            }
        }

        $conversations = \array_map(static function (Conversation $conversation) {
            if ($conversation->userID === WCF::getUser()->userID) {
                if ($conversation->participants > 1) {
                    $image = FontAwesomeIcon::fromValues('users')->toHtml(48);
                    $usernames = \array_map(static fn($user) => $user->username, $conversation->getParticipantSummary());
                } else {
                    // @phpstan-ignore property.notFound
                    if ($conversation->otherParticipantID) {
                        $userProfile = UserProfileRuntimeCache::getInstance()->getObject($conversation->otherParticipantID);
                    } else {
                        // @phpstan-ignore property.notFound
                        $userProfile = UserProfile::getGuestUserProfile($conversation->otherParticipant);
                    }

                    $image = $userProfile->getAvatar()->getImageTag(48);
                    $usernames = [$userProfile->username];
                }
            } else {
                if ($conversation->participants > 1) {
                    $image = FontAwesomeIcon::fromValues('users')->toHtml(48);
                    $usernames = $conversation->getParticipantNames(true);
                } else {
                    $image = $conversation->getUserProfile()->getAvatar()->getImageTag(48);
                    $usernames = [$conversation->getUserProfile()->username];
                }
            }

            $link = LinkHandler::getInstance()->getControllerLink(
                ConversationPage::class,
                [
                    'object' => $conversation,
                    'action' => 'firstNew',
                ]
            );

            return [
                'content' => StringUtil::encodeHTML($conversation->getTitle()),
                'image' => $image,
                'isUnread' => $conversation->isNew(),
                'link' => $link,
                'objectId' => $conversation->conversationID,
                'time' => $conversation->lastPostTime,
                'usernames' => $usernames,
            ];
        }, $conversations);

        return [
            'items' => $conversations,
            'totalCount' => $totalCount,
        ];
    }

    /**
     * Rebuilds the conversation data of the relevant conversations.
     *
     * @return void
     */
    public function rebuild()
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        // collect number of messages for each conversation
        $conditionBuilder = new PreparedStatementConditionBuilder();
        $conditionBuilder->add('conversation_message.conversationID IN (?)', [$this->objectIDs]);
        $sql = "SELECT      conversationID, COUNT(messageID) AS messages, SUM(attachments) AS attachments
                FROM        wcf1_conversation_message conversation_message
                " . $conditionBuilder . "
                GROUP BY    conversationID";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditionBuilder->getParameters());

        $objectIDs = [];
        while ($row = $statement->fetchArray()) {
            if (!$row['messages']) {
                continue;
            }
            $objectIDs[] = $row['conversationID'];

            $conversationEditor = new ConversationEditor(new Conversation(null, [
                'conversationID' => $row['conversationID'],
            ]));
            $conversationEditor->update([
                'attachments' => $row['attachments'],
                'replies' => $row['messages'] - 1,
            ]);
            $conversationEditor->updateFirstMessage();
            $conversationEditor->updateLastMessage();
        }

        // delete conversations without messages
        $deleteConversationIDs = \array_diff($this->objectIDs, $objectIDs);
        if (!empty($deleteConversationIDs)) {
            $conversationAction = new self($deleteConversationIDs, 'delete');
            $conversationAction->executeAction();
        }
    }
}
