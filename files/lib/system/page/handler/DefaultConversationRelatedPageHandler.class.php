<?php
namespace wcf\system\page\handler;

/**
 * Default implementation of a board-related page handler.
 * 
 * Only use this class when you need the online location handling for a board-related page.
 *
 * @author	Matthias Schmidt
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.page.handler
 * @category	Community Framework
 * @since	2.2
 */
class DefaultConversationRelatedPageHandler extends AbstractMenuPageHandler implements IOnlineLocationPageHandler {
	use TConversationOnlineLocationPageHandler;
}
