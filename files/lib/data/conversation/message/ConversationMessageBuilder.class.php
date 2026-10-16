<?php

namespace wcf\data\conversation\message;

use wcf\data\DatabaseObject;
use wcf\data\DatabaseObjectBuilder;
use wcf\system\attachment\AttachmentHandler;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\message\embedded\object\MessageEmbeddedObjectManager;

/**
 * Builder for creating and updating conversation messages.
 *
 * @author      Marcel Werk
 * @copyright   2001-2026 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.3
 *
 * @extends DatabaseObjectBuilder<ConversationMessage>
 */
final class ConversationMessageBuilder extends DatabaseObjectBuilder
{
    public private(set) AttachmentHandler $attachmentHandler;

    public private(set) HtmlInputProcessor $htmlInputProcessor;

    public function setConversationID(int $conversationID): static
    {
        $this->properties['conversationID'] = $conversationID;

        return $this;
    }

    public function setUserID(?int $userID): static
    {
        $this->properties['userID'] = $userID;

        return $this;
    }

    public function setUsername(string $username): static
    {
        $this->properties['username'] = $username;

        return $this;
    }

    public function setMessage(string $message): static
    {
        $this->properties['message'] = $message;

        return $this;
    }

    public function setTime(int $time): static
    {
        $this->properties['time'] = $time;

        return $this;
    }

    public function setAttachments(int $attachments): static
    {
        $this->properties['attachments'] = $attachments;

        return $this;
    }

    public function setEnableHtml(bool $enableHtml): static
    {
        $this->properties['enableHtml'] = $enableHtml ? 1 : 0;

        return $this;
    }

    public function setIpAddress(string $ipAddress): static
    {
        $this->properties['ipAddress'] = $ipAddress;

        return $this;
    }

    public function setLastEditTime(int $lastEditTime): static
    {
        $this->properties['lastEditTime'] = $lastEditTime;

        return $this;
    }

    public function incrementEditCount(int $editCount): static
    {
        $this->incrementProperties['editCount'] = $editCount;

        return $this;
    }

    public function setHasEmbeddedObjects(bool $hasEmbeddedObjects): static
    {
        $this->properties['hasEmbeddedObjects'] = $hasEmbeddedObjects ? 1 : 0;

        return $this;
    }

    public function setAttachmentHandler(AttachmentHandler $attachmentHandler): static
    {
        $this->attachmentHandler = $attachmentHandler;
        $this->properties['attachments'] = \count($attachmentHandler);

        return $this;
    }

    public function setHtmlInputProcessor(HtmlInputProcessor $htmlInputProcessor): static
    {
        $this->htmlInputProcessor = $htmlInputProcessor;
        $this->setMessage($this->htmlInputProcessor->getHtml());
        $this->setEnableHtml(true);

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->properties['message'] ?? null;
    }

    #[\Override]
    protected function afterCreate(DatabaseObject $object): void
    {
        if (isset($this->htmlInputProcessor)) {
            $this->registerEmbeddedObjects($this->htmlInputProcessor, $object);
        }

        if (isset($this->attachmentHandler)) {
            $this->attachmentHandler->updateObjectID($object->messageID);
        }
    }

    #[\Override]
    protected function afterUpdate(DatabaseObject $object): void
    {
        if (isset($this->htmlInputProcessor)) {
            $this->registerEmbeddedObjects($this->htmlInputProcessor, $object);
        }
    }

    private function registerEmbeddedObjects(HtmlInputProcessor $processor, ConversationMessage $message): void
    {
        $processor->setObjectID($message->messageID);
        ConversationMessageBuilder::forUpdate($message)
            ->setHasEmbeddedObjects(
                MessageEmbeddedObjectManager::getInstance()->registerObjects($processor)
            )
            ->update();
    }

    #[\Override]
    protected function getRequiredProperties(): array
    {
        return ['conversationID', 'message'];
    }
}
