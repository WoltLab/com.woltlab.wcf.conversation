<?php
namespace wcf\data\conversation;
use wcf\data\DatabaseObjectList;

/**
 * Represents a list of conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation
 * @category	Community Framework
 *
 * @method	Conversation		current()
 * @method	Conversation[]		getObjects()
 * @method	Conversation|null	search($objectID)
 */
class ConversationList extends DatabaseObjectList {
	/**
	 * @inheritDoc
	 */
	public $className = Conversation::class;
}
