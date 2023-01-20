<?php

namespace wcf\system\worker;

use wcf\data\conversation\ConversationList;
use wcf\data\conversation\message\ConversationMessageList;
use wcf\system\search\SearchIndexManager;
use wcf\system\WCF;

/**
 * Worker implementation for updating the search index of conversation messages.
 *
 * @author  Tim Duesterhus
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @method  ConversationMessageList     getObjectList()
 */
final class ConversationMessageSearchIndexRebuildDataWorker extends AbstractRebuildDataWorker
{
    /**
     * @inheritDoc
     */
    protected $limit = 1000;

    /**
     * @inheritDoc
     */
    public function countObjects()
    {
        if ($this->count === null) {
            $this->count = 0;
            $sql = "SELECT  MAX(messageID) AS messageID
                    FROM    wcf" . WCF_N . "_conversation_message";
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

        // read associated conversations
        $conversationIDs = \array_column(
            $this->getObjectList()->getObjects(),
            'conversationID'
        );

        $threadList = new ConversationList();
        $threadList->setObjectIDs($conversationIDs);
        $threadList->readObjects();
        $conversations = $threadList->getObjects();

        foreach ($this->getObjectList() as $message) {
            $message->setConversation($conversations[$message->conversationID]);

            $subject = '';
            if ($message->messageID == $message->getConversation()->firstMessageID) {
                $subject = $message->getTitle();
            }

            SearchIndexManager::getInstance()->set(
                'com.woltlab.wcf.conversation.message',
                $message->messageID,
                $message->message,
                $subject,
                $message->time,
                $message->userID,
                $message->username
            );
        }
    }
}
