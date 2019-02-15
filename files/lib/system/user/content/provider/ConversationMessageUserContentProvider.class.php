<?php
namespace wcf\system\user\content\provider;
use wcf\data\conversation\message\ConversationMessage;

/**
 * User content provider for conversation messages.
 *
 * @author	Joshua Ruesweg
 * @copyright	2001-2019 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\User\Content\Provider
 * @since	5.2
 */
class ConversationMessageUserContentProvider extends AbstractDatabaseUserContentProvider {
	/**
	 * @inheritdoc
	 */
	public static function getDatabaseObjectClass() {
		return ConversationMessage::class;
	}
}
