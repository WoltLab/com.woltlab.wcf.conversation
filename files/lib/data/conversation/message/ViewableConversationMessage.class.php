<?php
namespace wcf\data\conversation\message;
use wcf\data\user\UserProfile;
use wcf\data\DatabaseObjectDecorator;
use wcf\data\TLegacyUserPropertyAccess;
use wcf\system\cache\runtime\UserProfileRuntimeCache;

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
				$this->userProfile = UserProfileRuntimeCache::getInstance()->getObject($this->userID);
			}
			else {
				$this->userProfile = UserProfile::getGuestUserProfile($this->username);
			}
		}
		
		return $this->userProfile;
	}
	
	/**
	 * Returns the viewable conversation message with the given id.
	 * 
	 * @param	integer		$messageID
	 * @return	\wcf\data\conversation\message\ViewableConversationMessage
	 */
	public static function getViewableConversationMessage($messageID) {
		$messageList = new ViewableConversationMessageList();
		$messageList->setObjectIDs(array($messageID));
		$messageList->readObjects();
		
		return $messageList->search($messageID);
	}
}
