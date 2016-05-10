<?php
namespace wcf\data\conversation\message;
use wcf\data\DatabaseObjectList;

/**
 * Represents a list of conversation messages.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation.message
 * @category	Community Framework
 *
 * @method	ConversationMessage		current()
 * @method	ConversationMessage[]		getObjects()
 * @method	ConversationMessage|null	search($objectID)
 */
class ConversationMessageList extends DatabaseObjectList {
	/**
	 * @inheritDoc
	 */
	public $className = ConversationMessage::class;
}
