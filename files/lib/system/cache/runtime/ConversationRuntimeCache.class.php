<?php
namespace wcf\system\cache\runtime;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationList;

/**
 * Runtime cache implementation for conversations.
 *
 * @author	Matthias Schmidt
 * @copyright	2001-2017 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\Cache\Runtime
 * @since	3.0
 * 
 * @method	Conversation[]		getCachedObjects()
 * @method	Conversation		getObject($objectID)
 * @method	Conversation[]		getObjects(array $objectIDs)
 */
class ConversationRuntimeCache extends AbstractRuntimeCache {
	/**
	 * @inheritDoc
	 */
	protected $listClassName = ConversationList::class;
}
