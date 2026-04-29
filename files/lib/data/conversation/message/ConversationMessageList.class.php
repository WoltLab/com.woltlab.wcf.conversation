<?php

namespace wcf\data\conversation\message;

use wcf\data\attachment\GroupedAttachmentList;
use wcf\data\DatabaseObjectDecorator;
use wcf\data\DatabaseObjectList;

/**
 * Represents a list of conversation messages.
 *
 * @author      Marcel Werk
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @template TDatabaseObject of ConversationMessage|DatabaseObjectDecorator<ConversationMessage> = ConversationMessage
 * @extends DatabaseObjectList<TDatabaseObject>
 */
class ConversationMessageList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = ConversationMessage::class;

    /**
     * @since 6.2
     */
    protected ?GroupedAttachmentList $attachments;

    #[\Override]
    public function readObjects()
    {
        if ($this->objectIDs === null) {
            $this->readObjectIDs();
        }

        parent::readObjects();
    }

    /**
     * @since 6.2
     */
    public function getMaxTime(): int
    {
        $maxTime = 0;
        foreach ($this->getObjects() as $message) {
            if ($message->time > $maxTime) {
                $maxTime = $message->time;
            }
        }

        return $maxTime;
    }

    /**
     * @since 6.2
     */
    public function getAttachments(): ?GroupedAttachmentList
    {
        if (!isset($this->attachments)) {
            $this->attachments = null;
            $attachmentObjectIDs = $this->getAttachmentObjectIDs();
            if ($attachmentObjectIDs !== []) {
                $this->attachments = new GroupedAttachmentList('com.woltlab.wcf.conversation.message');
                $this->attachments->getConditionBuilder()
                    ->add('attachment.objectID IN (?)', [$attachmentObjectIDs]);
                $this->attachments->readObjects();
            } else {
                $this->attachments = null;
            }
        }

        return $this->attachments;
    }

    /**
     * @return list<int>
     * @since 6.2
     */
    protected function getAttachmentObjectIDs(): array
    {
        $objectIDs = [];
        foreach ($this->getObjects() as $message) {
            if ($message->attachments !== 0) {
                $objectIDs[] = $message->getObjectID();
            }
        }

        return $objectIDs;
    }
}
