<?php

namespace wcf\system\worker;

use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\message\ConversationMessageList;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\bbcode\BBCodeHandler;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\message\embedded\object\MessageEmbeddedObjectManager;
use wcf\system\search\SearchIndexManager;
use wcf\system\WCF;

/**
 * Worker implementation for updating conversation messages.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package WoltLabSuite\Core\System\Worker
 *
 * @method  ConversationMessageList     getObjectList()
 */
class ConversationMessageRebuildDataWorker extends AbstractRebuildDataWorker
{
    /**
     * @inheritDoc
     */
    protected $limit = 500;

    /**
     * @var HtmlInputProcessor
     */
    protected $htmlInputProcessor;

    /**
     * @inheritDoc
     */
    public function countObjects()
    {
        if ($this->count === null) {
            $this->count = 0;
            $sql = "SELECT	MAX(messageID) AS messageID
				FROM	wcf" . WCF_N . "_conversation_message";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute();
            $row = $statement->fetchArray();
            if ($row !== false) {
                $this->count = $row['messageID'];
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function initObjectList()
    {
        $this->objectList = new ConversationMessageList();
        $this->objectList->sqlOrderBy = 'conversation_message.messageID';
        $this->objectList->sqlSelects = '(SELECT subject FROM wcf' . WCF_N . '_conversation WHERE conversationID = conversation_message.conversationID) AS subject';
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $this->objectList->getConditionBuilder()->add(
            'conversation_message.messageID BETWEEN ? AND ?',
            [$this->limit * $this->loopCount + 1, $this->limit * $this->loopCount + $this->limit]
        );

        parent::execute();

        if (!$this->loopCount) {
            // reset search index
            SearchIndexManager::getInstance()->reset('com.woltlab.wcf.conversation.message');
        }

        if (!\count($this->objectList)) {
            return;
        }

        // prepare statements
        $attachmentObjectType = ObjectTypeCache::getInstance()
            ->getObjectTypeByName('com.woltlab.wcf.attachment.objectType', 'com.woltlab.wcf.conversation.message');
        $sql = "SELECT		COUNT(*) AS attachments
			FROM		wcf" . WCF_N . "_attachment
			WHERE		objectTypeID = ?
					AND objectID = ?";
        $attachmentStatement = WCF::getDB()->prepareStatement($sql);

        // retrieve permissions
        $userIDs = [];
        foreach ($this->objectList as $object) {
            // passing `0` is actually valid, because it won't yield any results when querying the group membership
            $userIDs[] = ($object->userID ?: 0);
        }
        $userPermissions = $this->getBulkUserPermissions($userIDs, ['user.message.disallowedBBCodes']);

        $updateData = [];
        /** @var ConversationMessage $message */
        foreach ($this->objectList as $message) {
            SearchIndexManager::getInstance()->set(
                'com.woltlab.wcf.conversation.message',
                $message->messageID,
                $message->message,
                $message->subject ?: '',
                $message->time,
                $message->userID,
                $message->username
            );

            $data = [];

            // count attachments
            $attachmentStatement->execute([$attachmentObjectType->objectTypeID, $message->messageID]);
            $data['attachments'] = $attachmentStatement->fetchSingleColumn();

            BBCodeHandler::getInstance()->setDisallowedBBCodes(\explode(
                ',',
                $this->getBulkUserPermissionValue($userPermissions, $message->userID, 'user.message.disallowedBBCodes')
            ));

            // update message
            $data['enableHtml'] = 1;
            if (!$message->enableHtml) {
                $this->getHtmlInputProcessor()->process(
                    $message->message,
                    'com.woltlab.wcf.conversation.message',
                    $message->messageID,
                    true
                );
                $data['message'] = $this->getHtmlInputProcessor()->getHtml();
            } else {
                $this->getHtmlInputProcessor()->reprocess(
                    $message->message,
                    'com.woltlab.wcf.conversation.message',
                    $message->messageID
                );
                $data['message'] = $this->getHtmlInputProcessor()->getHtml();
            }

            if (MessageEmbeddedObjectManager::getInstance()->registerObjects($this->getHtmlInputProcessor(), true)) {
                $data['hasEmbeddedObjects'] = 1;
            } else {
                $data['hasEmbeddedObjects'] = 0;
            }

            $updateData[$message->messageID] = $data;
        }

        $sql = "UPDATE  wcf" . WCF_N . "_conversation_message
			SET     attachments = ?,
				message = ?,
				enableHtml = ?,
				hasEmbeddedObjects = ?
			WHERE   messageID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);

        WCF::getDB()->beginTransaction();
        foreach ($updateData as $messageID => $data) {
            $statement->execute([
                $data['attachments'],
                $data['message'],
                $data['enableHtml'],
                $data['hasEmbeddedObjects'],
                $messageID,
            ]);
        }
        WCF::getDB()->commitTransaction();

        MessageEmbeddedObjectManager::getInstance()->commitBulkOperation();
    }

    /**
     * @return  HtmlInputProcessor
     */
    protected function getHtmlInputProcessor()
    {
        if ($this->htmlInputProcessor === null) {
            $this->htmlInputProcessor = new HtmlInputProcessor();
        }

        return $this->htmlInputProcessor;
    }
}
