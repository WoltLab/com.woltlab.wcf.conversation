<?php

namespace wcf\data\conversation\message;

use wcf\data\attachment\GroupedAttachmentList;
use wcf\data\conversation\Conversation;
use wcf\data\DatabaseObject;
use wcf\data\IMessage;
use wcf\data\TUserContent;
use wcf\system\html\output\HtmlOutputProcessor;
use wcf\system\message\embedded\object\MessageEmbeddedObjectManager;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Represents a conversation message.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @property-read   int $messageID      unique id of the conversation message
 * @property-read   int $conversationID     id of the conversation the conversation message belongs to
 * @property-read   int|null $userID         id of the user who wrote the conversation message or `null` if the user does not exist anymore
 * @property-read   string $username       name of the user who wrote the conversation message
 * @property-read   string $message        text of the conversation message
 * @property-read   int $time           timestamp at which the conversation message has been written
 * @property-read   int $attachments        number of attachments
 * @property-read   int $enableHtml     is `1` if the conversation message's format has been converted to html, otherwise `0`
 * @property-read   string $ipAddress      ip address of the user who wrote the conversation message at time of writing or empty if no ip addresses are logged
 * @property-read   int $lastEditTime       timestamp at which the conversation message has been edited the last time
 * @property-read   int $editCount      number of times the conversation message has been edited
 * @property-read   int $hasEmbeddedObjects number of embedded objects in the conversation message
 */
class ConversationMessage extends DatabaseObject implements IMessage
{
    use TUserContent;

    /**
     * conversation object
     * @var Conversation
     */
    protected $conversation;

    /**
     * @inheritDoc
     */
    public function getFormattedMessage(): string
    {
        $processor = new HtmlOutputProcessor();
        $processor->process($this->message, 'com.woltlab.wcf.conversation.message', $this->messageID);

        return $processor->getHtml();
    }

    /**
     * Returns a simplified version of the formatted message.
     */
    public function getSimplifiedFormattedMessage(): string
    {
        $processor = new HtmlOutputProcessor();
        $processor->setOutputType('text/simplified-html');
        $processor->process($this->message, 'com.woltlab.wcf.conversation.message', $this->messageID);

        return $processor->getHtml();
    }

    /**
     * Assigns and returns the embedded attachments.
     *
     * @param bool $ignoreCache
     * @return  null|GroupedAttachmentList
     */
    public function getAttachments($ignoreCache = false)
    {
        if ($this->attachments || $ignoreCache) {
            $attachmentList = new GroupedAttachmentList('com.woltlab.wcf.conversation.message');
            $attachmentList->getConditionBuilder()->add('attachment.objectID IN (?)', [$this->messageID]);
            $attachmentList->readObjects();
            $attachmentList->setPermissions([
                'canDownload' => true,
                'canViewPreview' => true,
            ]);

            if ($ignoreCache && !\count($attachmentList)) {
                return null;
            }

            return $attachmentList;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getExcerpt($maxLength = 255): string
    {
        return StringUtil::truncateHTML($this->getSimplifiedFormattedMessage(), $maxLength);
    }

    /**
     * Returns a version of this message optimized for use in emails.
     *
     * @param string $mimeType Either 'text/plain' or 'text/html'
     */
    public function getMailText($mimeType = 'text/plain'): string
    {
        if ($this->hasEmbeddedObjects) {
            MessageEmbeddedObjectManager::getInstance()->loadObjects(
                'com.woltlab.wcf.conversation.message',
                [$this->messageID]
            );
        }

        switch ($mimeType) {
            case 'text/plain':
                $processor = new HtmlOutputProcessor();
                $processor->setOutputType('text/plain');
                $processor->process($this->message, 'com.woltlab.wcf.conversation.message', $this->messageID);

                return $processor->getHtml();
            case 'text/html':
                return $this->getSimplifiedFormattedMessage();
        }

        throw new \LogicException('Unreachable');
    }

    /**
     * Returns the conversation of this message.
     *
     * @return  Conversation
     */
    public function getConversation()
    {
        if ($this->conversation === null) {
            $this->conversation = Conversation::getUserConversation($this->conversationID, WCF::getUser()->userID);
        }

        return $this->conversation;
    }

    /**
     * Sets the conversation of this message.
     *
     * @param Conversation $conversation
     */
    public function setConversation(Conversation $conversation)
    {
        if ($this->conversationID == $conversation->conversationID) {
            $this->conversation = $conversation;
        }
    }

    /**
     * Returns true if current user may edit this message.
     */
    public function canEdit(): bool
    {
        return WCF::getUser()->userID == $this->userID
            && (
                $this->getConversation()->isDraft
                || WCF::getSession()->getPermission('user.conversation.canEditMessage')
            )
            && $this->getConversation()->canReply();
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @inheritDoc
     */
    public function getLink(): string
    {
        return LinkHandler::getInstance()->getLink('Conversation', [
            'object' => $this->getConversation(),
            'messageID' => $this->messageID,
            'forceFrontend' => true,
        ], '#message' . $this->messageID);
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        if ($this->messageID == $this->getConversation()->firstMessageID) {
            return $this->getConversation()->subject;
        }

        return 'RE: ' . $this->getConversation()->subject;
    }

    /**
     * @inheritDoc
     */
    public function isVisible(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->getFormattedMessage();
    }
}
