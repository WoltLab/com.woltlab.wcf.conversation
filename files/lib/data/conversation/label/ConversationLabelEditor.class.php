<?php
namespace wcf\data\conversation\label;
use wcf\data\DatabaseObjectEditor;

/**
 * Extends the label object with functions to create, update and delete labels.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation.label
 * @category	Community Framework
 * 
 * @method	ConversationLabel	getDecoratedObject()
 * @mixin	ConversationLabel
 */
class ConversationLabelEditor extends DatabaseObjectEditor {
	/**
	 * @inheritDoc
	 */
	protected static $baseClass = ConversationLabel::class;
}
