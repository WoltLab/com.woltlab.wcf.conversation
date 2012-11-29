<?php
namespace wcf\page;
use wcf\data\conversation\Conversation;
use wcf\system\breadcrumb\Breadcrumb;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;

/**
 * Shows the conversation log page.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	page
 * @category	Community Framework
 */
class ConversationLogPage extends SortablePage {
	/**
	 * conversation id
	 * @var integer
	 */
	public $conversationID = 0;
	
	/**
	 * conversation object
	 * @var wcf\data\conversation\Conversation
	 */
	public $conversation = null;
	
	/**
	 * @see	wcf\page\SortablePage::$defaultSortField
	 */
	public $defaultSortField = 'time';
	
	/**
	 * @see	wcf\page\SortablePage::$defaultSortOrder
	 */
	public $defaultSortOrder = 'DESC';
	
	/**
	 * @see	wcf\page\MultipleLinkPage::$objectListClassName
	 */
	public $objectListClassName = 'wcf\data\modification\log\ConversationLogModificationLogList';
	
	/**
	 * @see	wcf\page\SortablePage::$validSortFields
	 */
	public $validSortFields = array('logID', 'time', 'username');
	
	/**
	 * @see wcf\page\IPage::readParameters()
	 */
	public function readParameters() {
		parent::readParameters();
		
		if (isset($_REQUEST['id'])) $this->conversationID = intval($_REQUEST['id']);
		$this->conversation = new Conversation($this->conversationID);
		if (!$this->conversation->conversationID) {
			throw new IllegalLinkException();
		}
		
		if (!Conversation::isParticipant(array($this->conversation->conversationID))) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * @see wcf\page\MultipleLinkPage::initObjectList()
	 */
	protected function initObjectList() {
		parent::initObjectList();
		
		$this->objectList->setConversation($this->conversation);
	}
	
	/**
	 * @see wcf\page\IPage::readData()
	 */
	public function readData() {
		parent::readData();
		
		// add breadcrumbs
		WCF::getBreadcrumbs()->add(new Breadcrumb(WCF::getLanguage()->get('wcf.conversation.conversations'), LinkHandler::getInstance()->getLink('ConversationList')));
		WCF::getBreadcrumbs()->add($this->conversation->getBreadcrumb());
	}
	
	/**
	 * @see wcf\page\IPage::assignVariables()
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		WCF::getTPL()->assign(array(
			'conversation' => $this->conversation
		));
	}
}
 