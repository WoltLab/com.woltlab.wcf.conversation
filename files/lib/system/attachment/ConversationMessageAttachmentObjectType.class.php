<?php

namespace wcf\system\attachment;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\message\ConversationMessageList;
use wcf\system\WCF;
use wcf\util\ArrayUtil;

/**
 * Attachment object type implementation for conversations.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @method  ConversationMessage getObject($objectID)
 */
class ConversationMessageAttachmentObjectType extends AbstractAttachmentObjectType
{
    /**
     * @inheritDoc
     */
    public function getMaxSize()
    {
        return WCF::getSession()->getPermission('user.conversation.maxAttachmentSize');
    }

    /**
     * @inheritDoc
     */
    public function getAllowedExtensions()
    {
        return ArrayUtil::trim(\explode(
            "\n",
            WCF::getSession()->getPermission('user.conversation.allowedAttachmentExtensions')
        ));
    }

    /**
     * @inheritDoc
     */
    public function getMaxCount()
    {
        return WCF::getSession()->getPermission('user.conversation.maxAttachmentCount');
    }

    /**
     * @inheritDoc
     */
    public function canDownload($objectID)
    {
        if ($objectID) {
            $message = new ConversationMessage($objectID);
            $conversation = Conversation::getUserConversation($message->conversationID, WCF::getUser()->userID);
            if ($conversation !== null && $conversation->canRead()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function canUpload($objectID, $parentObjectID = 0)
    {
        if (!WCF::getSession()->getPermission('user.conversation.canUploadAttachment')) {
            return false;
        }

        if ($objectID) {
            $message = new ConversationMessage($objectID);
            if ($message->userID == WCF::getUser()->userID) {
                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function canDelete($objectID)
    {
        if ($objectID) {
            $message = new ConversationMessage($objectID);
            if ($message->userID == WCF::getUser()->userID) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function cacheObjects(array $objectIDs)
    {
        $messageList = new ConversationMessageList();
        $messageList->setObjectIDs($objectIDs);
        $messageList->readObjects();
        $conversationIDs = [];
        foreach ($messageList as $message) {
            $conversationIDs[] = $message->conversationID;
        }
        if (!empty($conversationIDs)) {
            $conversations = Conversation::getUserConversations($conversationIDs, WCF::getUser()->userID);
            foreach ($messageList as $message) {
                if (isset($conversations[$message->conversationID])) {
                    $message->setConversation($conversations[$message->conversationID]);
                }
            }
        }

        foreach ($messageList->getObjects() as $objectID => $object) {
            $this->cachedObjects[$objectID] = $object;
        }
    }

    /**
     * @inheritDoc
     */
    public function setPermissions(array $attachments)
    {
        $messageIDs = [];
        foreach ($attachments as $attachment) {
            // set default permissions
            $attachment->setPermissions([
                'canDownload' => false,
                'canViewPreview' => false,
            ]);

            if ($this->getObject($attachment->objectID) === null) {
                $messageIDs[] = $attachment->objectID;
            }
        }

        if (!empty($messageIDs)) {
            $this->cacheObjects($messageIDs);
        }

        foreach ($attachments as $attachment) {
            if (($message = $this->getObject($attachment->objectID)) !== null) {
                if (!$message->getConversation()->canRead()) {
                    continue;
                }

                $attachment->setPermissions([
                    'canDownload' => true,
                    'canViewPreview' => true,
                ]);
            } elseif ($attachment->tmpHash != '' && $attachment->userID == WCF::getUser()->userID) {
                $attachment->setPermissions([
                    'canDownload' => true,
                    'canViewPreview' => true,
                ]);
            }
        }
    }
}
