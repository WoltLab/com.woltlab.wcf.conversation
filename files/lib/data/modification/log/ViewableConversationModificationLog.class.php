<?php

namespace wcf\data\modification\log;

use wcf\data\DatabaseObjectDecorator;
use wcf\data\user\UserProfile;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\WCF;

/**
 * Provides a viewable conversation modification log.
 *
 * @author  Alexander Ebert
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @mixin ModificationLog
 * @extends DatabaseObjectDecorator<ModificationLog>
 */
class ViewableConversationModificationLog extends DatabaseObjectDecorator
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = ModificationLog::class;

    /**
     * user profile object
     * @var ?UserProfile
     */
    protected $userProfile;

    /**
     * Returns readable representation of current log entry.
     *
     * @return  string
     */
    #[\Override]
    public function __toString()
    {
        return WCF::getLanguage()->getDynamicVariable(
            'wcf.conversation.log.conversation.' . $this->action,
            ['additionalData' => $this->additionalData]
        );
    }

    /**
     * Returns the profile object of the user who created the modification entry.
     *
     * @return UserProfile
     */
    public function getUserProfile()
    {
        if ($this->userProfile === null) {
            if ($this->userID !== null) {
                $this->userProfile = UserProfileRuntimeCache::getInstance()->getObject($this->userID);
            } else {
                $this->userProfile = UserProfile::getGuestUserProfile($this->username);
            }
        }

        return $this->userProfile;
    }
}
