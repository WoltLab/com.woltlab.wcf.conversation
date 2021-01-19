<?php

namespace wcf\data\conversation\message;

use wcf\data\DatabaseObjectDecorator;
use wcf\data\TLegacyUserPropertyAccess;
use wcf\data\user\UserProfile;
use wcf\system\cache\runtime\UserProfileRuntimeCache;

/**
 * Represents a viewable conversation message.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package WoltLabSuite\Core\Data\Conversation\Message
 *
 * @method  ConversationMessage getDecoratedObject()
 * @mixin   ConversationMessage
 */
class ViewableConversationMessage extends DatabaseObjectDecorator
{
    use TLegacyUserPropertyAccess;

    /**
     * @inheritDoc
     */
    protected static $baseClass = ConversationMessage::class;

    /**
     * user profile object
     * @var UserProfile
     */
    protected $userProfile;

    /**
     * Returns the user profile object.
     *
     * @return  UserProfile
     */
    public function getUserProfile()
    {
        if ($this->userProfile === null) {
            if ($this->userID) {
                $this->userProfile = UserProfileRuntimeCache::getInstance()->getObject($this->userID);
            } else {
                $this->userProfile = UserProfile::getGuestUserProfile($this->username);
            }
        }

        return $this->userProfile;
    }

    /**
     * Returns the viewable conversation message with the given id.
     *
     * @param int $messageID
     * @return  ViewableConversationMessage
     */
    public static function getViewableConversationMessage($messageID)
    {
        $messageList = new ViewableConversationMessageList();
        $messageList->setObjectIDs([$messageID]);
        $messageList->readObjects();

        return $messageList->search($messageID);
    }
}
