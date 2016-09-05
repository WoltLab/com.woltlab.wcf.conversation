<?php
namespace wcf\page;
use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\UserConversationList;
use wcf\system\breadcrumb\Breadcrumb;
use wcf\system\clipboard\ClipboardHandler;
use wcf\system\exception\IllegalLinkException;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;

/**
 * Shows a list of conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	page
 * @category	Community Framework
 */
class ConversationListPage extends SortablePage {
	/**
	 * @see	\wcf\page\AbstractPage::$enableTracking
	 */
	public $enableTracking = true;
	
	/**
	 * @see	\wcf\page\SortablePage::$defaultSortField
	 */
	public $defaultSortField = CONVERSATION_LIST_DEFAULT_SORT_FIELD;
	
	/**
	 * @see	\wcf\page\SortablePage::$defaultSortOrder
	 */
	public $defaultSortOrder = CONVERSATION_LIST_DEFAULT_SORT_ORDER;
	
	/**
	 * @see	\wcf\page\SortablePage::$validSortFields
	 */
	public $validSortFields = array('subject', 'time', 'username', 'lastPostTime', 'replies', 'participants');
	
	/**
	 * @see	\wcf\page\MultipleLinkPage::$itemsPerPage
	 */
	public $itemsPerPage = CONVERSATIONS_PER_PAGE;
	
	/**
	 * @see	\wcf\page\AbstractPage::$loginRequired
	 */
	public $loginRequired = true;
	
	/**
	 * @see	\wcf\page\AbstractPage::$neededModules
	 */
	public $neededModules = array('MODULE_CONVERSATION');
	
	/**
	 * @see	\wcf\page\AbstractPage::$neededPermissions
	 */
	public $neededPermissions = array('user.conversation.canUseConversation');
	
	/**
	 * list filter
	 * @var	string
	 */
	public $filter = '';
	
	/**
	 * label id
	 * @var	integer
	 */
	public $labelID = 0;
	
	/**
	 * label list object
	 * @var	\wcf\data\conversation\label\ConversationLabelList
	 */
	public $labelList = null;
	
	/**
	 * number of conversations (no filter)
	 * @var	integer
	 */
	public $conversationCount = 0;
	
	/**
	 * number of drafts
	 * @var	integer
	 */
	public $draftCount = 0;
	
	/**
	 * number of hidden conversations
	 * @var	integer
	 */
	public $hiddenCount = 0;
	
	/**
	 * number of sent conversations
	 * @var	integer
	 */
	public $outboxCount = 0;
	
	/**
	 * @see	\wcf\page\IPage::readParameters()
	 */
	public function readParameters() {
		parent::readParameters();
		
		if (isset($_REQUEST['filter'])) $this->filter = $_REQUEST['filter'];
		if (!in_array($this->filter, UserConversationList::$availableFilters)) $this->filter = '';
		
		// user settings
		if (WCF::getUser()->conversationsPerPage) $this->itemsPerPage = WCF::getUser()->conversationsPerPage;
		
		// labels
		$this->labelList = ConversationLabel::getLabelsByUser();
		if (isset($_REQUEST['labelID'])) {
			$this->labelID = intval($_REQUEST['labelID']);
			
			$validLabel = false;
			foreach ($this->labelList as $label) {
				if ($label->labelID == $this->labelID) {
					$validLabel = true;
					break;
				}
			}
			
			if (!$validLabel) {
				throw new IllegalLinkException();
			}
		}
	}
	
	/**
	 * @see	\wcf\page\MultipleLinkPage::initObjectList()
	 */
	protected function initObjectList() {
		$this->objectList = new UserConversationList(WCF::getUser()->userID, $this->filter, $this->labelID);
		$this->objectList->setLabelList($this->labelList);
	}
	
	/**
	 * @see	\wcf\page\IPage::readData()
	 */
	public function readData() {
		// if sort field is `username`, `conversation.` has to prepended because `username`
		// alone is ambiguous 
		if ($this->sortField === 'username') {
			$this->sortField = 'conversation.username';
		}
		
		parent::readData();
		
		// change back to old value
		if ($this->sortField === 'conversation.username') {
			$this->sortField = 'username';
		}
		
		if ($this->filter != '') {
			// add breadcrumbs
			WCF::getBreadcrumbs()->add(new Breadcrumb(WCF::getLanguage()->get('wcf.conversation.conversations'), LinkHandler::getInstance()->getLink('ConversationList')));
		}
		
		// read stats
		if (!$this->labelID) {
			switch ($this->filter) {
				case '':
					$this->conversationCount = $this->items;
				break;
				
				case 'draft':
					$this->draftCount = $this->items;
				break;
				
				case 'hidden':
					$this->hiddenCount = $this->items;
				break;
				
				case 'outbox':
					$this->outboxCount = $this->items;
				break;
			}
		}
		
		if ($this->filter != '' || $this->labelID) {
			$conversationList = new UserConversationList(WCF::getUser()->userID, '');
			$this->conversationCount = $conversationList->countObjects();
		}
		if ($this->filter != 'draft' || $this->labelID) {
			$conversationList = new UserConversationList(WCF::getUser()->userID, 'draft');
			$this->draftCount = $conversationList->countObjects();
		}
		if ($this->filter != 'hidden' || $this->labelID) {
			$conversationList = new UserConversationList(WCF::getUser()->userID, 'hidden');
			$this->hiddenCount = $conversationList->countObjects();
		}
		if ($this->filter != 'outbox' || $this->labelID) {
			$conversationList = new UserConversationList(WCF::getUser()->userID, 'outbox');
			$this->outboxCount = $conversationList->countObjects();
		}
	}
	
	/**
	 * @see	\wcf\page\IPage::assignVariables()
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		WCF::getTPL()->assign(array(
			'filter' => $this->filter,
			'hasMarkedItems' => ClipboardHandler::getInstance()->hasMarkedItems(ClipboardHandler::getInstance()->getObjectTypeID('com.woltlab.wcf.conversation.conversation')),
			'labelID' => $this->labelID,
			'labelList' => $this->labelList,
			'conversationCount' => $this->conversationCount,
			'draftCount' => $this->draftCount,
			'hiddenCount' => $this->hiddenCount,
			'outboxCount' => $this->outboxCount
		));
	}
}
