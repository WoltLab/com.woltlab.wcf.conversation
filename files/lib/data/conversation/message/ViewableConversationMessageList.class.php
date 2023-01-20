<?php

namespace wcf\data\conversation\message;

use wcf\data\attachment\GroupedAttachmentList;
use wcf\data\conversation\Conversation;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\message\embedded\object\MessageEmbeddedObjectManager;

/**
 * Represents a list of viewable conversation messages.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @method  ViewableConversationMessage     current()
 * @method  ViewableConversationMessage[]       getObjects()
 * @method  ViewableConversationMessage|null    getSingleObject()
 * @method  ViewableConversationMessage|null    search($objectID)
 * @property    ViewableConversationMessage[] $objects
 */
class ViewableConversationMessageList extends ConversationMessageList
{
    /**
     * @inheritDoc
     */
    public $sqlOrderBy = 'conversation_message.time';

    /**
     * @inheritDoc
     */
    public $decoratorClassName = ViewableConversationMessage::class;

    /**
     * attachment object ids
     * @var int[]
     */
    public $attachmentObjectIDs = [];

    /**
     * ids of the messages with embedded objects
     * @var int[]
     */
    public $embeddedObjectMessageIDs = [];

    /**
     * attachment list
     * @var GroupedAttachmentList
     */
    protected $attachmentList;

    /**
     * max post time
     * @var int
     */
    protected $maxPostTime = 0;

    /**
     * enables/disables the loading of attachments
     * @var bool
     */
    protected $attachmentLoading = true;

    /**
     * enables/disables the loading of embedded objects
     * @var bool
     */
    protected $embeddedObjectLoading = true;

    /**
     * conversation object
     * @var Conversation
     */
    protected $conversation;

    /**
     * @inheritDoc
     */
    public function readObjects()
    {
        if ($this->objectIDs === null) {
            $this->readObjectIDs();
        }

        parent::readObjects();

        $userIDs = [];
        foreach ($this->objects as $message) {
            if ($message->time > $this->maxPostTime) {
                $this->maxPostTime = $message->time;
            }
            if ($this->conversation !== null) {
                $message->setConversation($this->conversation);
            }

            if ($message->attachments) {
                $this->attachmentObjectIDs[] = $message->messageID;
            }

            if ($message->hasEmbeddedObjects) {
                $this->embeddedObjectMessageIDs[] = $message->messageID;
            }
            if ($message->userID) {
                $userIDs[] = $message->userID;
            }
        }

        if (!empty($userIDs)) {
            UserProfileRuntimeCache::getInstance()->cacheObjectIDs($userIDs);
        }

        if ($this->embeddedObjectLoading) {
            $this->readEmbeddedObjects();
        }
        if ($this->attachmentLoading) {
            $this->readAttachments();
        }
    }

    /**
     * Reads the embedded objects of the messages in the list.
     */
    public function readEmbeddedObjects()
    {
        if (!empty($this->embeddedObjectMessageIDs)) {
            // add message objects to attachment object cache to save SQL queries
            ObjectTypeCache::getInstance()
                ->getObjectTypeByName('com.woltlab.wcf.attachment.objectType', 'com.woltlab.wcf.conversation.message')
                ->getProcessor()
                ->setCachedObjects($this->objects);

            // load embedded objects
            MessageEmbeddedObjectManager::getInstance()
                ->loadObjects('com.woltlab.wcf.conversation.message', $this->embeddedObjectMessageIDs);
        }
    }

    /**
     * Reads the list of attachments.
     */
    public function readAttachments()
    {
        if (!empty($this->attachmentObjectIDs)) {
            $this->attachmentList = new GroupedAttachmentList('com.woltlab.wcf.conversation.message');
            $this->attachmentList->getConditionBuilder()
                ->add('attachment.objectID IN (?)', [$this->attachmentObjectIDs]);
            $this->attachmentList->readObjects();
        }
    }

    /**
     * Returns the max post time.
     *
     * @return  int
     */
    public function getMaxPostTime()
    {
        return $this->maxPostTime;
    }

    /**
     * Returns the list of attachments.
     *
     * @return  GroupedAttachmentList
     */
    public function getAttachmentList()
    {
        return $this->attachmentList;
    }

    /**
     * Enables/disables the loading of attachments.
     *
     * @param bool $enable
     */
    public function enableAttachmentLoading($enable = true)
    {
        $this->attachmentLoading = $enable;
    }

    /**
     * Enables/disables the loading of embedded objects.
     *
     * @param bool $enable
     */
    public function enableEmbeddedObjectLoading($enable = true)
    {
        $this->embeddedObjectLoading = $enable;
    }

    /**
     * Sets active conversation.
     *
     * @param Conversation $conversation
     */
    public function setConversation(Conversation $conversation)
    {
        $this->conversation = $conversation;
    }
}
