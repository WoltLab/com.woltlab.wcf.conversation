<?php
namespace wcf\system\page\handler;
use wcf\data\page\Page;
use wcf\data\user\online\UserOnline;
use wcf\system\cache\runtime\UserConversationRuntimeCache;
use wcf\system\WCF;

/**
 * Implementation of the online location-related page handler methods for conversations.
 * 
 * @author	Matthias Schmidt
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.page.handler
 * @category	Community Framework
 * @since	2.2
 */
trait TConversationOnlineLocationPageHandler {
	use TOnlineLocationPageHandler;
	
	/**
	 * @see	IMenuPageHandler::getOnlineLocation()
	 */
	public function getOnlineLocation(Page $page, UserOnline $user) {
		if ($user->objectID === null) {
			return '';
		}
		
		$conversation = UserConversationRuntimeCache::getInstance()->getObject($user->objectID);
		if ($conversation === null || !$conversation->canRead()) {
			return '';
		}
		
		return WCF::getLanguage()->getDynamicVariable('wcf.page.onlineLocation.'.$page->identifier, ['conversation' => $conversation]);
	}
	
	/**
	 * @see	IOnlineLocationPageHandler::prepareOnlineLocation()
	 */
	public function prepareOnlineLocation(Page $page, UserOnline $user) {
		if ($user->objectID !== null) {
			UserConversationRuntimeCache::getInstance()->cacheObjectID($user->objectID);
		}
	}
}
