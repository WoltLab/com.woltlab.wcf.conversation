<?php
namespace wcf\data\conversation\label;
use wcf\data\AbstractDatabaseObjectAction;

/**
 * Executes label-related actions.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation.label
 * @category 	Community Framework
 */
class ConversationLabelAction extends AbstractDatabaseObjectAction {
	/**
	 * @see wcf\data\AbstractDatabaseObjectAction::$className
	 */
	protected $className = 'wcf\data\conversation\label\ConversationLabelEditor';
}
