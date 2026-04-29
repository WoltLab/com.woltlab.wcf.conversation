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
 * @extends AbstractRebuildDataWorker<ConversationMessageList>
 */
final class ConversationMessageSearchIndexRebuildDataWorker extends AbstractRebuildDataWorker
{
    /**
     * @inheritDoc
     */
    protected $limit = 1000;

    #[\Override]
    public function countObjects()
    {
        if ($this->count === null) {
            $this->count = 0;
            $sql = "SELECT  MAX(messageID) AS messageID
                    FROM    wcf1_conversation_message";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute();
            $row = $statement->fetchArray();
            if ($row !== false) {
                $this->count = $row['messageID'];
            }
        }
    }

    #[\Override]
    protected function initObjectList()
    {
        $this->objectList = new ConversationMessageList();
        $this->objectList->sqlOrderBy = 'conversation_message.messageID';
    }

    #[\Override]
    public function execute()
    {
        $this->objectList->getConditionBuilder()->add(
            'conversation_message.messageID BETWEEN ? AND ?',
            [$this->limit * $this->loopCount + 1, $this->limit * $this->loopCount + $this->limit]
        );

        parent::execute();

        if ($this->loopCount === 0) {
            // reset search index
            SearchIndexManager::getInstance()->reset('com.woltlab.wcf.conversation.message');
        }

        if (\count($this->objectList) === 0) {
            return;
        }

        foreach ($this->getObjectList() as $message) {
            $subject = '';
            if ($message->messageID === $message->getConversation()->firstMessageID) {
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
