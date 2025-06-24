<?php

namespace wcf\data\modification\log;

use wcf\data\DatabaseObject;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\log\modification\ConversationModificationLogHandler;
use wcf\system\WCF;

/**
 * Represents a list of modification logs for conversation log page.
 *
 * @author  Alexander Ebert
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends ModificationLogList<ViewableConversationModificationLog>
 */
class ConversationLogModificationLogList extends ModificationLogList
{
    /**
     * @inheritDoc
     */
    public function __construct(int $conversationID)
    {
        parent::__construct();

        // set conditions
        $this->getConditionBuilder()->add(
            'modification_log.objectTypeID = ?',
            [ConversationModificationLogHandler::getInstance()->getObjectType('com.woltlab.wcf.conversation.conversation')->objectTypeID]
        );
        $this->getConditionBuilder()->add(
            'modification_log.objectID = ?',
            [$conversationID]
        );
    }

    /**
     * @inheritDoc
     */
    public function readObjects()
    {
        $sql = "SELECT  modification_log.*
                FROM    wcf1_modification_log modification_log
                " . $this->getConditionBuilder() . "
                " . (!empty($this->sqlOrderBy) ? "ORDER BY " . $this->sqlOrderBy : '');
        $statement = WCF::getDB()->prepare($sql, $this->sqlLimit, $this->sqlOffset);
        $statement->execute($this->getConditionBuilder()->getParameters());
        // @phpstan-ignore assign.propertyType, argument.templateType
        $this->objects = $statement->fetchObjects(($this->objectClassName ?: $this->className));

        // use table index as array index
        $objects = $userIDs = [];
        foreach ($this->objects as $object) {
            /** @var ModificationLog $object */
            $objectID = $object->{$this->getDatabaseTableIndexName()};
            $objects[$objectID] = $object;

            $this->indexToObject[] = $objectID;

            if ($object->userID) {
                $userIDs[] = $object->userID;
            }
        }

        if ($userIDs !== []) {
            UserProfileRuntimeCache::getInstance()->cacheObjectIDs($userIDs);
        }

        $this->objectIDs = $this->indexToObject;
        $this->objects = \array_map(
            static fn(DatabaseObject $object) => new ViewableConversationModificationLog($object),
            $objects
        );
    }

    /**
     * Returns all log entries created before given point of time. Applicable entries
     * will be returned and removed from collection.
     *
     * @param int $time
     * @return  ViewableConversationModificationLog[]
     */
    public function getEntriesUntil($time)
    {
        $entries = [];
        foreach ($this->objects as $index => $entry) {
            if ($entry->time < $time) {
                $entries[] = $entry;
                unset($this->objects[$index]);
            }
        }

        if (!empty($entries)) {
            $this->indexToObject = \array_keys($this->objects);
        }

        return $entries;
    }
}
