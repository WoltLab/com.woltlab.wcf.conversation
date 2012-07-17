<?php
namespace wcf\page;
use wcf\data\conversation\UserConversationList;
use wcf\system\breadcrumb\Breadcrumb;
use wcf\system\clipboard\ClipboardHandler;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;

/**
 * Shows a list of conversations.
 *
 * @author	Marcel Werk
 * @copyright	2009-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	page
 * @category 	Community Framework
 */
class ConversationListPage extends SortablePage {
	/**
	 * @see wcf\page\SortablePage::$defaultSortField
	 */
	public $defaultSortField = CONVERSATION_LIST_DEFAULT_SORT_FIELD;
	
	/**
	 * @see wcf\page\SortablePage::$defaultSortOrder
	 */
	public $defaultSortOrder = CONVERSATION_LIST_DEFAULT_SORT_ORDER;
	
	/**
	 * @see wcf\page\SortablePage::$validSortFields
	 */
	public $validSortFields = array('subject', 'time', 'username', 'lastPostTime', 'replies', 'participants');
	
	/**
	 * @see wcf\page\MultipleLinkPage::$itemsPerPage
	 */
	public $itemsPerPage = CONVERSATIONS_PER_PAGE;
	
	/**
	 * @see wcf\page\AbstractPage::$neededModules
	 */
	public $neededModules = array('MODULE_CONVERSATION');
	
	/**
	 * @see wcf\page\AbstractPage::$neededPermissions
	 */
	public $neededPermissions = array('user.conversation.canUseConversation');
	
	/**
	 * list filter
	 * @var string
	 */
	public $filter = '';
	
	/**
	 * @see wcf\page\IPage::readParameters()
	 */
	public function readParameters() {
		parent::readParameters();
		
		if (isset($_REQUEST['filter'])) $this->filter = $_REQUEST['filter'];
		if (!in_array($this->filter, UserConversationList::$availableFilters)) $this->filter = '';
		
		// user settings
		if (WCF::getUser()->conversationsPerPage) $this->itemsPerPage = WCF::getUser()->conversationsPerPage;
	}
	
	/**
	 * @see wcf\page\MultipleLinkPage::initObjectList()
	 */
	protected function initObjectList() {
		$this->objectList = new UserConversationList(WCF::getUser()->userID, $this->filter);
	}
	
	/**
	 * @see wcf\page\IPage::readData()
	 */
	public function readData() {
		parent::readData();
		
		if ($this->filter != '') {
			// add breadcrumbs
			WCF::getBreadcrumbs()->add(new Breadcrumb(WCF::getLanguage()->get('wcf.conversation.conversations'), LinkHandler::getInstance()->getLink('ConversationList')));
		}
	}
	
	/**
	 * @see wcf\page\IPage::assignVariables()
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		WCF::getTPL()->assign(array(
			'filter' => $this->filter,
			'hasMarkedItems' => ClipboardHandler::getInstance()->hasMarkedItems(ClipboardHandler::getInstance()->getObjectTypeID('com.woltlab.wcf.conversation.conversation'))
		));
	}
	
	/**
	 * @see wcf\page\IPage::show()
	 */
	public function show() {
		if (!WCF::getUser()->userID) {
			throw new PermissionDeniedException();
		}
		
		parent::show();
	}
}
