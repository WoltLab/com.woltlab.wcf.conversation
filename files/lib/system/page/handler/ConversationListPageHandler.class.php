<?php
namespace wcf\system\page\handler;
use wcf\system\conversation\ConversationHandler;
use wcf\system\WCF;

/**
 * Page handler implementation for the conversation list.
 *
 * @author	Matthias Schmidt
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.page.handler
 * @category	Community Framework
 * @since	2.2
 */
class ConversationListPageHandler extends AbstractMenuPageHandler {
	/**
	 * @inheritDoc
	 */
	public function getOutstandingItemCount($objectID = null) {
		return ConversationHandler::getInstance()->getUnreadConversationCount();
	}
	
	/**
	 * @inheritDoc
	 */
	public function isVisible($objectID = null) {
		return WCF::getUser()->userID != 0;
	}
}
