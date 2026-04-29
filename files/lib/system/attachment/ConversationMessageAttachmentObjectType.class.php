<?php

namespace wcf\system\attachment;

use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\message\ConversationMessageList;
use wcf\system\cache\runtime\ConversationRuntimeCache;
use wcf\system\WCF;
use wcf\util\ArrayUtil;

/**
 * Attachment object type implementation for conversations.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractAttachmentObjectType<ConversationMessage>
 */
class ConversationMessageAttachmentObjectType extends AbstractAttachmentObjectType
{
    #[\Override]
    public function getMaxSize()
    {
        return WCF::getSession()->getPermission('user.conversation.maxAttachmentSize');
    }

    #[\Override]
    public function getAllowedExtensions()
    {
        return ArrayUtil::trim(\explode(
            "\n",
            WCF::getSession()->getPermission('user.conversation.allowedAttachmentExtensions')
        ));
    }

    #[\Override]
    public function getMaxCount()
    {
        return WCF::getSession()->getPermission('user.conversation.maxAttachmentCount');
    }

    #[\Override]
    public function canDownload(int $objectID)
    {
        if ($objectID !== 0) {
            $message = new ConversationMessage($objectID);
            $conversation = ConversationRuntimeCache::getInstance()->getObject($message->conversationID);
            if ($conversation !== null && $conversation->canRead()) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function canUpload(int $objectID, int $parentObjectID = 0)
    {
        if (!WCF::getSession()->hasPermission('user.conversation.canUploadAttachment')) {
            return false;
        }

        if ($objectID !== 0) {
            $message = new ConversationMessage($objectID);
            if ($message->userID === WCF::getUser()->userID) {
                return true;
            }

            return false;
        }

        return true;
    }

    #[\Override]
    public function canDelete(int $objectID)
    {
        if ($objectID !== 0) {
            $message = new ConversationMessage($objectID);
            if ($message->userID === WCF::getUser()->userID) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function cacheObjects(array $objectIDs)
    {
        $messageList = new ConversationMessageList();
        $messageList->setObjectIDs($objectIDs);
        $messageList->readObjects();

        foreach ($messageList->getObjects() as $objectID => $object) {
            $this->cachedObjects[$objectID] = $object;
        }
    }

    #[\Override]
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

        if ($messageIDs !== []) {
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
            } elseif ($attachment->tmpHash !== '' && $attachment->userID === WCF::getUser()->userID) {
                $attachment->setPermissions([
                    'canDownload' => true,
                    'canViewPreview' => true,
                ]);
            }
        }
    }
}
