<?php
namespace wcf\data\conversation\label;
use wcf\data\DatabaseObjectList;

/**
 * Represents a list of conversation labels.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation.label
 * @category	Community Framework
 */
class ConversationLabelList extends DatabaseObjectList {
	/**
	 * @see	\wcf\data\DatabaseObjectList::$className
	 */
	public $className = 'wcf\data\conversation\label\ConversationLabel';
}
