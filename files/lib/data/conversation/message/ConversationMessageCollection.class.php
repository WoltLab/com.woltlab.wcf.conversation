<?php

namespace wcf\data\conversation\message;

use wcf\data\conversation\Conversation;
use wcf\data\DatabaseObjectCollection;
use wcf\data\object\type\ObjectTypeCache;
use wcf\data\user\UserProfile;
use wcf\system\cache\runtime\ConversationRuntimeCache;
use wcf\system\cache\runtime\FileRuntimeCache;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\file\processor\ImageData;
use wcf\system\message\embedded\object\MessageEmbeddedObjectManager;
use wcf\system\WCF;

/**
 * Represents a collection of conversation messages.
 *
 * @author      Marcel Werk
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 *
 * @extends DatabaseObjectCollection<ConversationMessage>
 */
class ConversationMessageCollection extends DatabaseObjectCollection
{
    /**
     * @var array<int, Conversation>
     */
    private array $conversations;

    /**
     * @var array<int, ImageData>
     */
    private array $teaserImages;

    private bool $embeddedObjectsLoaded = false;
    private bool $userProfilesLoaded = false;

    public function getConversation(ConversationMessage $object): ?Conversation
    {
        $this->loadConversations();

        return $this->conversations[$object->conversationID] ?? null;
    }

    private function loadConversations(): void
    {
        if (isset($this->conversations)) {
            return;
        }

        $this->conversations = [];
        $conversationIDs = \array_map(static fn($message) => $message->conversationID, $this->getObjects());
        $conversationIDs = \array_unique($conversationIDs);

        if ($conversationIDs !== []) {
            $this->conversations = ConversationRuntimeCache::getInstance()->getObjects($conversationIDs);
        }
    }

    public function loadEmbeddedObjects(): void
    {
        if ($this->embeddedObjectsLoaded) {
            return;
        }

        $this->embeddedObjectsLoaded = true;

        // Add message objects to attachment object cache to save SQL queries.
        ObjectTypeCache::getInstance()
            ->getObjectTypeByName('com.woltlab.wcf.attachment.objectType', 'com.woltlab.wcf.conversation.message')
            ->getProcessor()
            ->setCachedObjects($this->getObjects());

        $objectIDs = $this->getEmbeddedObjectIDs();
        if ($objectIDs === []) {
            return;
        }

        MessageEmbeddedObjectManager::getInstance()
            ->loadObjects('com.woltlab.wcf.conversation.message', $objectIDs);
    }

    public function getUserProfile(ConversationMessage $message): UserProfile
    {
        $this->loadUserProfiles();

        if ($message->userID) {
            return UserProfileRuntimeCache::getInstance()->getObject($message->userID);
        } else {
            return UserProfile::getGuestUserProfile($message->username);
        }
    }

    private function loadUserProfiles(): void
    {
        if ($this->userProfilesLoaded) {
            return;
        }

        $this->userProfilesLoaded = true;

        $userIDs = [];
        foreach ($this->getObjects() as $object) {
            if ($object->userID) {
                $userIDs[] = $object->userID;
            }
        }

        if ($userIDs !== []) {
            UserProfileRuntimeCache::getInstance()->cacheObjectIDs($userIDs);
        }
    }

    /**
     * @return int[]
     */
    private function getEmbeddedObjectIDs(): array
    {
        return \array_map(
            fn($content) => $content->getObjectID(),
            \array_filter($this->getObjects(), fn($content) => !!$content->hasEmbeddedObjects)
        );
    }

    public function getTeaserImage(ConversationMessage $message): ?ImageData
    {
        $this->loadTeaserImages();

        return $this->teaserImages[$message->getObjectID()] ?? null;
    }

    private function loadTeaserImages(): void
    {
        if (isset($this->teaserImages)) {
            return;
        }

        $this->teaserImages = [];

        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add("objectTypeID = ?", [ObjectTypeCache::getInstance()->getObjectTypeIDByName(
            'com.woltlab.wcf.attachment.objectType',
            'com.woltlab.wcf.conversation.message'
        )]);
        $conditions->add("objectID IN (?)", [$this->getObjectIDs()]);
        $conditions->add("fileID IN (SELECT fileID FROM wcf1_file WHERE mimeType IN (?))", [[
            'image/gif',
            'image/jpeg',
            'image/png',
            'image/webp'
        ]]);

        $sql = "SELECT  objectID, fileID
                FROM    (
                            SELECT  objectID, fileID,
                                    ROW_NUMBER() OVER (PARTITION BY objectID ORDER BY showOrder) AS row_num
                            FROM    wcf1_attachment
                            {$conditions}
                        ) AS subselect
                WHERE   subselect.row_num <= ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute(
            \array_merge($conditions->getParameters(), [1])
        );

        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            FileRuntimeCache::getInstance()->cacheObjectID($row['fileID']);
        }

        foreach ($rows as $row) {
            $this->teaserImages[$row['objectID']] = FileRuntimeCache::getInstance()->getObject($row['fileID'])->getImageData(320, 200);
        }
    }
}
