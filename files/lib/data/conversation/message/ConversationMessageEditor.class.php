<?php
namespace wcf\data\conversation\message;
use wcf\data\DatabaseObjectEditor;

/**
 * Extends the message object with functions to create, update and delete messages.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2017 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\Data\Conversation\Message
 * 
 * @method static	ConversationMessage	create(array $parameters = [])
 * @method		ConversationMessage	getDecoratedObject()
 * @mixin		ConversationMessage
 */
class ConversationMessageEditor extends DatabaseObjectEditor {
	/**
	 * @inheritDoc
	 */
	protected static $baseClass = ConversationMessage::class;
}
