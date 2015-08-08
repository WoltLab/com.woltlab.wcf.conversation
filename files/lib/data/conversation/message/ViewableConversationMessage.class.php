<?php
namespace wcf\data\conversation\message;
use wcf\data\user\User;
use wcf\data\user\UserProfile;
use wcf\data\user\UserProfileCache;
use wcf\data\DatabaseObjectDecorator;
use wcf\data\TLegacyUserPropertyAccess;

/**
 * Represents a viewable conversation message.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2015 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation.message
 * @category	Community Framework
 */
class ViewableConversationMessage extends DatabaseObjectDecorator {
	use TLegacyUserPropertyAccess;
	
	/**
	 * @see	\wcf\data\DatabaseObjectDecorator::$baseClass
	 */
	protected static $baseClass = 'wcf\data\conversation\message\ConversationMessage';
	
	/**
	 * user profile object
	 * @var	\wcf\data\user\UserProfile
	 */
	protected $userProfile = null;
	
	/**
	 * Returns the user profile object.
	 * 
	 * @return	\wcf\data\user\UserProfile
	 */
	public function getUserProfile() {
		if ($this->userProfile === null) {
			if ($this->userID) {
				$this->userProfile = UserProfileCache::getInstance()->getUserProfile($this->userID);
			}
			else {
				$this->userProfile = new UserProfile(new User(null, array(
					'username' => $this->username
				)));
			}
		}
		
		return $this->userProfile;
	}
}
