<?php

namespace wcf\data\conversation\message;

use wcf\data\conversation\Conversation;
use wcf\data\DatabaseObjectCollection;
use wcf\data\object\type\ObjectTypeCache;
use wcf\data\user\UserProfile;
use wcf\system\cache\runtime\ConversationRuntimeCache;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\message\embedded\object\MessageEmbeddedObjectManager;

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
            fn($content) => $content->recordID,
            \array_filter($this->getObjects(), fn($content) => $content->hasEmbeddedObjects)
        );
    }
}
