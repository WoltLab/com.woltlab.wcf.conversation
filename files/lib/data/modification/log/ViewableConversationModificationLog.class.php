<?php
namespace wcf\data\modification\log;
use wcf\data\user\User;
use wcf\data\user\UserProfile;
use wcf\data\DatabaseObjectDecorator;
use wcf\system\WCF;

/**
 * Provides a viewable conversation modification log.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2015 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.modification.log
 * @category	Community Framework
 */
class ViewableConversationModificationLog extends DatabaseObjectDecorator {
	/**
	 * @see	\wcf\data\DatabaseObjectDecorator::$baseClass
	 */
	protected static $baseClass = 'wcf\data\modification\log\ModificationLog';
	
	/**
	 * user profile object
	 * @var	\wcf\data\user\UserProfile
	 */
	protected $userProfile = null;
	
	/**
	 * Returns readable representation of current log entry.
	 */
	public function __toString() {
		return WCF::getLanguage()->getDynamicVariable('wcf.conversation.log.conversation.'.$this->action, array('additionalData' => $this->additionalData));
	}
	
	/**
	 * Returns the profile object of the user who created the modification entry.
	 * 
	 * @return	\wcf\data\user\UserProfile
	 */
	public function getUserProfile() {
		if ($this->userProfile === null) {
			$this->userProfile = new UserProfile(new User(null, $this->getDecoratedObject()->data));
		}
		
		return $this->userProfile;
	}
}
