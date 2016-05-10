<?php
namespace wcf\data\conversation\label;
use wcf\data\DatabaseObjectList;

/**
 * Represents a list of conversation labels.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation.label
 * @category	Community Framework
 * 
 * @method	ConversationLabel		current()
 * @method	ConversationLabel[]		getObjects()
 * @method	ConversationLabel|null		search($objectID)
 */
class ConversationLabelList extends DatabaseObjectList {
	/**
	 * @inheritDoc
	 */
	public $className = ConversationLabel::class;
}
