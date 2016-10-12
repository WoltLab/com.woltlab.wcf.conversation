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
 * @package	WoltLabSuite\Core\System\Page\Handler
 * @since	3.0
 */
class ConversationListPageHandler extends AbstractMenuPageHandler {
	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * @inheritDoc
	 */
	public function getOutstandingItemCount($objectID = null) {
		return ConversationHandler::getInstance()->getUnreadConversationCount();
	}
	
	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * @inheritDoc
	 */
	public function isVisible($objectID = null) {
		return WCF::getUser()->userID != 0;
	}
}
