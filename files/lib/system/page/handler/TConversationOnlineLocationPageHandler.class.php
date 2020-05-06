<?php
namespace wcf\system\page\handler;
use wcf\data\conversation\Conversation;
use wcf\data\page\Page;
use wcf\data\user\online\UserOnline;
use wcf\system\cache\runtime\UserConversationRuntimeCache;
use wcf\system\WCF;

/**
 * Implementation of the online location-related page handler methods for conversations.
 * 
 * @author	Matthias Schmidt
 * @copyright	2001-2018 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\Page\Handler
 * @since	3.0
 */
trait TConversationOnlineLocationPageHandler {
	use TOnlineLocationPageHandler;
	
	/**
	 * Returns the textual description if a user is currently online viewing this page.
	 *
	 * @see	IOnlineLocationPageHandler::getOnlineLocation()
	 *
	 * @param	Page		$page		visited page
	 * @param	UserOnline	$user		user online object with request data
	 * @return	string
	 */
	public function getOnlineLocation(Page $page, UserOnline $user) {
		if ($user->pageObjectID === null) {
			return '';
		}
		
		$conversation = UserConversationRuntimeCache::getInstance()->getObject($user->pageObjectID);
		if ($conversation === null || !$conversation->canRead()) {
			return '';
		}
		
		if ($conversation->userID != WCF::getUser()->userID && $user->userID != WCF::getUser()->userID) {
			// Make sure that requests from invisible participants are not listed
			// if the active user is not the author of the conversation.
			$userConversation = Conversation::getUserConversation($conversation->conversationID, $user->userID);
			if ($userConversation !== null && $userConversation->isInvisible) {
				return '';
			}
		}
		
		return WCF::getLanguage()->getDynamicVariable('wcf.page.onlineLocation.'.$page->identifier, ['conversation' => $conversation]);
	}
	
	/**
	 * Prepares fetching all necessary data for the textual description if a user is currently online
	 * viewing this page.
	 *
	 * @see	IOnlineLocationPageHandler::prepareOnlineLocation()
	 *
	 * @param	Page		$page		visited page
	 * @param	UserOnline	$user		user online object with request data
	 */
	public function prepareOnlineLocation(/** @noinspection PhpUnusedParameterInspection */Page $page, UserOnline $user) {
		if ($user->pageObjectID !== null) {
			UserConversationRuntimeCache::getInstance()->cacheObjectID($user->pageObjectID);
		}
	}
}
