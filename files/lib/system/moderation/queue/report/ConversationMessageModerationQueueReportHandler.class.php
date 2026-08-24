<?php

namespace wcf\system\moderation\queue\report;

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

    /**
     * @inheritDoc
     */
    public function assignQueues(array $queues)
    {
        $assignments = [];
        foreach ($queues as $queue) {
            $assignUser = false;
            if (WCF::getSession()->getPermission('mod.conversation.canModerateConversation')) {
                $assignUser = true;
            }

            $assignments[$queue->queueID] = $assignUser;
        }

        ModerationQueueManager::getInstance()->setAssignment($assignments);
    }

    /**
     * @inheritDoc
     */
    public function canReport($objectID)
    {
        if (\MODULE_CONVERSATION === 0) {
            return false;
        }

        if (!WCF::getSession()->getPermission('user.conversation.canUseConversation')) {
            return false;
        }

        if (!$this->isValid($objectID)) {
            return false;
        }

        return $this->getMessage($objectID)->canRead();
    }

    /**
     * @inheritDoc
     */
    public function getContainerID($objectID)
    {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function getReportedContent(ViewableModerationQueue $queue)
    {
        return WCF::getTPL()->render('wcf', 'moderationConversationMessage', [
            'message' => new ConversationMessage($queue->objectID),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getReportedObject($objectID)
    {
        if ($this->isValid($objectID)) {
            return $this->getMessage($objectID);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function isValid($objectID)
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
            if (!self::$messages[$objectID]->messageID) {
                self::$messages[$objectID] = null;
            }
        }

        return self::$messages[$objectID];
    }

    /**
     * @inheritDoc
     */
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
        return WCF::getSession()->getPermission('mod.conversation.canModerateConversation');
    }

    /**
     * @inheritDoc
     */
    public function removeContent(ModerationQueue $queue, $message)
    {
        if ($this->isValid($queue->objectID)) {
            $messageAction = new ConversationMessageAction([$this->getMessage($queue->objectID)], 'delete');
            $messageAction->executeAction();
        }
    }
}
