<?php
namespace wcf\data\conversation\label;
use wcf\data\DatabaseObjectEditor;

/**
 * Extends the label object with functions to create, update and delete labels.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation.label
 * @category	Community Framework
 */
class ConversationLabelEditor extends DatabaseObjectEditor {
	/**
	 * @see	wcf\data\DatabaseObjectEditor::$baseClass
	 */
	protected static $baseClass = 'wcf\data\conversation\label\ConversationLabel';
}
