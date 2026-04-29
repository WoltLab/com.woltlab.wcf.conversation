<?php

namespace wcf\system\importer;

use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\message\ConversationMessageEditor;
use wcf\data\object\type\ObjectTypeCache;

/**
 * Imports conversation attachments.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class ConversationAttachmentImporter extends AbstractAttachmentImporter
{
    public function __construct()
    {
        $objectType = ObjectTypeCache::getInstance()
            ->getObjectTypeByName('com.woltlab.wcf.attachment.objectType', 'com.woltlab.wcf.conversation.message');
        $this->objectTypeID = $objectType->objectTypeID;
    }

    #[\Override]
    public function import(mixed $oldID, array $data, array $additionalData = [])
    {
        $data['objectID'] = ImportHandler::getInstance()
            ->getNewID('com.woltlab.wcf.conversation.message', $data['objectID']);
        if ($data['objectID'] === null) {
            return 0;
        }

        $attachmentID = parent::import($oldID, $data, $additionalData);
        if ($attachmentID !== 0 && $attachmentID !== $oldID) {
            // fix embedded attachments
            $messageObj = new ConversationMessage($data['objectID']);

            if (($newMessage = $this->fixEmbeddedAttachments($messageObj->message, $oldID, $attachmentID)) !== false) {
                $editor = new ConversationMessageEditor($messageObj);
                $editor->update([
                    'message' => $newMessage,
                ]);
            }
        }

        return $attachmentID;
    }
}
