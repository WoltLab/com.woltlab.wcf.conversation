<?php
namespace wcf\system\cache\runtime;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\UserConversationList;
use wcf\system\WCF;

/**
 * Runtime cache implementation for conversation fetched using UserConversationList.
 *
 * @author	Matthias Schmidt
 * @copyright	2001-2018 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\Cache\Runtime
 * @since	3.0
 * 
 * @method	Conversation[]		getCachedObjects()
 * @method	Conversation		getObject($objectID)
 * @method	Conversation[]		getObjects(array $objectIDs)
 */
class UserConversationRuntimeCache extends AbstractRuntimeCache {
	/**
	 * @inheritDoc
	 */
	protected $listClassName = UserConversationList::class;
	
	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * @inheritDoc
	 */
	protected function getObjectList() {
		return new UserConversationList(WCF::getUser()->userID);
	}
}
