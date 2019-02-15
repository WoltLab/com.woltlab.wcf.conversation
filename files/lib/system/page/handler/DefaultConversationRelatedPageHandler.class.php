<?php
namespace wcf\system\page\handler;

/**
 * Default implementation of a board-related page handler.
 * 
 * Only use this class when you need the online location handling for a board-related page.
 *
 * @author	Matthias Schmidt
 * @copyright	2001-2019 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\Page\Handler
 * @since	3.0
 */
class DefaultConversationRelatedPageHandler extends AbstractMenuPageHandler implements IOnlineLocationPageHandler {
	use TConversationOnlineLocationPageHandler;
}
