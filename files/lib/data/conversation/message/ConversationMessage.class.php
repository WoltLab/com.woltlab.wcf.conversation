<?php

namespace wcf\data\conversation\message;

use wcf\data\attachment\GroupedAttachmentList;
use wcf\data\CollectionDatabaseObject;
use wcf\data\conversation\Conversation;
use wcf\data\IMessage;
use wcf\data\TUserContent;
use wcf\data\user\UserProfile;
use wcf\system\file\processor\ImageData;
use wcf\system\html\output\HtmlOutputProcessor;
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
 *
 * @extends CollectionDatabaseObject<ConversationMessageCollection>
 */
class ConversationMessage extends CollectionDatabaseObject implements IMessage
{
    use TUserContent;

    /**
     * conversation object
     * @var ?Conversation
     */
    protected $conversation;

    /**
     * @inheritDoc
     */
    public function getFormattedMessage(): string
    {
        $this->getCollection()->loadEmbeddedObjects();

        $processor = new HtmlOutputProcessor();
        $processor->process($this->message, 'com.woltlab.wcf.conversation.message', $this->messageID);

        return $processor->getHtml();
    }

    /**
     * Returns a simplified version of the formatted message.
     */
    public function getSimplifiedFormattedMessage(): string
    {
        $this->getCollection()->loadEmbeddedObjects();

        $processor = new HtmlOutputProcessor();
        $processor->setOutputType('text/simplified-html');
        $processor->process($this->message, 'com.woltlab.wcf.conversation.message', $this->messageID);

        return $processor->getHtml();
    }

    /**
     * Assigns and returns the embedded attachments.
     *
     * @return ?GroupedAttachmentList
     */
    public function getAttachments(bool $ignoreCache = false)
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
    public function getMailText(string $mimeType = 'text/plain'): string
    {
        $this->getCollection()->loadEmbeddedObjects();

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
     * @since 6.2
     */
    public function getTeaser(): string
    {
        $this->getCollection()->loadEmbeddedObjects();

        $processor = new HtmlOutputProcessor();
        $processor->setOutputType('text/plain');
        $processor->process($this->message, 'com.woltlab.wcf.conversation.message', $this->messageID);

        return StringUtil::truncate($processor->getHtml(), 255);
    }

    /**
     * @since 6.2
     */
    public function getTeaserImage(): ?ImageData
    {
        return $this->getCollection()->getTeaserImage($this);
    }

    public function getConversation(): ?Conversation
    {
        return $this->getCollection()->getConversation($this);
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

    /**
     * @since 6.2
     */
    public function getUserProfile(): UserProfile
    {
        return $this->getCollection()->getUserProfile($this);
    }

    /**
     * Checks if the current user can read this particular message.
     */
    public function canRead(): bool
    {
        $conversation = $this->getConversation();
        if ($conversation === null) {
            return false;
        }

        // Drafts are a special type of conversations that may or may not have
        // any participants. The author will only be added as a participant
        // after the conversation is no longer a draft.
        if ($conversation->isDraft && $conversation->userID && $conversation->userID === WCF::getUser()->userID) {
            return true;
        }

        $participant = $conversation->getParticipant();
        if ($participant === null) {
            return false;
        }

        if ($participant->hasJoinedAfter($this->time) || $participant->hasLeftBefore($this->time)) {
            return false;
        }

        return true;
    }
}
