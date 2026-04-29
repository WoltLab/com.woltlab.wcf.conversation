<?php

namespace wcf\system\moderation\queue\report;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\message\ConversationMessageAction;
use wcf\data\conversation\message\ConversationMessageList;
use wcf\data\moderation\queue\ModerationQueue;
use wcf\data\moderation\queue\ViewableModerationQueue;
use wcf\system\moderation\queue\AbstractModerationQueueHandler;
use wcf\system\moderation\queue\ModerationQueueManager;
use wcf\system\WCF;

/**
 * An implementation of IModerationQueueReportHandler for conversation messages.
 *
 * @author  Alexander Ebert
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class ConversationMessageModerationQueueReportHandler extends AbstractModerationQueueHandler implements
    IModerationQueueReportHandler
{
    /**
     * @inheritDoc
     */
    protected $className = ConversationMessage::class;

    /**
     * @inheritDoc
     */
    protected $definitionName = 'com.woltlab.wcf.moderation.report';

    /**
     * @inheritDoc
     */
    protected $objectType = 'com.woltlab.wcf.conversation.message';

    /**
     * list of conversation message
     * @var array<int, ?ConversationMessage>
     */
    protected static $messages = [];

    /**
     * @inheritDoc
     */
    protected $requiredPermission = 'mod.conversation.canModerateConversation';

    #[\Override]
    public function assignQueues(array $queues)
    {
        $assignments = [];
        foreach ($queues as $queue) {
            $assignUser = false;
            if (WCF::getSession()->hasPermission('mod.conversation.canModerateConversation')) {
                $assignUser = true;
            }

            $assignments[$queue->queueID] = $assignUser;
        }

        ModerationQueueManager::getInstance()->setAssignment($assignments);
    }

    #[\Override]
    public function canReport(int $objectID)
    {
        if (!$this->isValid($objectID)) {
            return false;
        }

        if (!Conversation::isParticipant([$this->getMessage($objectID)->conversationID])) {
            return false;
        }

        return true;
    }

    #[\Override]
    public function getContainerID(int $objectID)
    {
        return 0;
    }

    #[\Override]
    public function getReportedContent(ViewableModerationQueue $queue)
    {
        return WCF::getTPL()->render('wcf', 'moderationConversationMessage', [
            'message' => new ConversationMessage($queue->objectID),
        ]);
    }

    #[\Override]
    public function getReportedObject(int $objectID)
    {
        if ($this->isValid($objectID)) {
            return $this->getMessage($objectID);
        }

        return null;
    }

    #[\Override]
    public function isValid(int $objectID)
    {
        if ($this->getMessage($objectID) === null) {
            return false;
        }

        return true;
    }

    /**
     * Returns a conversation message object by message id or null if message id is invalid.
     *
     * @return ?ConversationMessage
     */
    protected function getMessage(int $objectID)
    {
        if (!\array_key_exists($objectID, self::$messages)) {
            self::$messages[$objectID] = new ConversationMessage($objectID);
            if (self::$messages[$objectID]->isNil()) {
                self::$messages[$objectID] = null;
            }
        }

        return self::$messages[$objectID];
    }

    #[\Override]
    public function populate(array $queues)
    {
        $objectIDs = [];
        foreach ($queues as $object) {
            $objectIDs[] = $object->objectID;
        }

        // fetch messages
        $messageList = new ConversationMessageList();
        $messageList->setObjectIDs($objectIDs);
        $messageList->readObjects();
        $messages = $messageList->getObjects();

        // set orphaned queues
        foreach ($queues as $queue) {
            if (!isset($messages[$queue->objectID])) {
                $queue->setIsOrphaned();
            }
        }

        foreach ($queues as $object) {
            if (isset($messages[$object->objectID])) {
                $object->setAffectedObject($messages[$object->objectID]);
            }
        }
    }

    #[\Override]
    public function canRemoveContent(ModerationQueue $queue)
    {
        return WCF::getSession()->hasPermission('mod.conversation.canModerateConversation');
    }

    #[\Override]
    public function removeContent(ModerationQueue $queue, string $message)
    {
        if ($this->isValid($queue->objectID)) {
            $messageAction = new ConversationMessageAction([$this->getMessage($queue->objectID)], 'delete');
            $messageAction->executeAction();
        }
    }
}
