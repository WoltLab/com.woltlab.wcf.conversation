<?php
namespace wcf\page;
use wcf\data\conversation\FeedConversationList;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Shows most recent conversations.
 * 
 * @author 	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	page
 * @category 	Community Framework
 */
class ConversationFeedPage extends AbstractFeedPage {
	/**
	 * @see	wcf\page\AbstractPage::$loginRequired
	 */
	public $loginRequired = true;
	
	/**
	 * @see wcf\page\IPage::readData()
	 */
	public function readData() {
		parent::readData();
		
		$this->items = new FeedConversationList();
		$this->items->getConditionBuilder()->add('conversation_to_user.participantID = ?', array(WCF::getUser()->userID));
		$this->items->getConditionBuilder()->add('conversation_to_user.hideConversation = ?', array(0));
		$this->items->sqlConditionJoins = "LEFT JOIN wcf".WCF_N."_conversation conversation ON (conversation.conversationID = conversation_to_user.conversationID)";
		$this->items->readObjects();
	}
}