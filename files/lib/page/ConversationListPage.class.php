<?php
namespace wcf\page;
use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\label\ConversationLabelList;
use wcf\data\conversation\UserConversationList;
use wcf\system\breadcrumb\Breadcrumb;
use wcf\system\clipboard\ClipboardHandler;
use wcf\system\exception\IllegalLinkException;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\ArrayUtil;

/**
 * Shows a list of conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	page
 * @category	Community Framework
 */
class ConversationListPage extends SortablePage {
	/**
	 * @inheritDoc
	 */
	public $enableTracking = true;
	
	/**
	 * @inheritDoc
	 */
	public $defaultSortField = CONVERSATION_LIST_DEFAULT_SORT_FIELD;
	
	/**
	 * @inheritDoc
	 */
	public $defaultSortOrder = CONVERSATION_LIST_DEFAULT_SORT_ORDER;
	
	/**
	 * @inheritDoc
	 */
	public $validSortFields = ['subject', 'time', 'username', 'lastPostTime', 'replies', 'participants'];
	
	/**
	 * @inheritDoc
	 */
	public $itemsPerPage = CONVERSATIONS_PER_PAGE;
	
	/**
	 * @inheritDoc
	 */
	public $loginRequired = true;
	
	/**
	 * @inheritDoc
	 */
	public $neededModules = ['MODULE_CONVERSATION'];
	
	/**
	 * @inheritDoc
	 */
	public $neededPermissions = ['user.conversation.canUseConversation'];
	
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
	 * @var	ConversationLabelList
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
	 * participant that
	 * @var	string[]
	 */
	public $participants = [];
	
	/**
	 * @inheritDoc
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
		
		if (isset($_REQUEST['participants'])) $this->participants = ArrayUtil::trim(explode(',', $_REQUEST['participants']));
	}
	
	/**
	 * @inheritDoc
	 */
	protected function initObjectList() {
		$this->objectList = new UserConversationList(WCF::getUser()->userID, $this->filter, $this->labelID);
		$this->objectList->setLabelList($this->labelList);
		
		if (!empty($this->participants)) {
			$this->objectList->getConditionBuilder()->add('conversation.conversationID IN (SELECT conversationID FROM wcf'.WCF_N.'_conversation_to_user WHERE username IN (?) GROUP BY conversationID HAVING COUNT(conversationID) = ?)', [
				$this->participants,
				count($this->participants)
			]);
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function readData() {
		parent::readData();
		
		if ($this->filter != '') {
			// add breadcrumbs
			WCF::getBreadcrumbs()->add(new Breadcrumb(WCF::getLanguage()->get('wcf.conversation.conversations'), LinkHandler::getInstance()->getLink('ConversationList')));
		}
		
		// read stats
		if (!$this->labelID && empty($this->participants)) {
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
		
		if ($this->filter != '' || $this->labelID || !empty($this->participants)) {
			$conversationList = new UserConversationList(WCF::getUser()->userID, '');
			$this->conversationCount = $conversationList->countObjects();
		}
		if ($this->filter != 'draft' || $this->labelID || !empty($this->participants)) {
			$conversationList = new UserConversationList(WCF::getUser()->userID, 'draft');
			$this->draftCount = $conversationList->countObjects();
		}
		if ($this->filter != 'hidden' || $this->labelID || !empty($this->participants)) {
			$conversationList = new UserConversationList(WCF::getUser()->userID, 'hidden');
			$this->hiddenCount = $conversationList->countObjects();
		}
		if ($this->filter != 'outbox' || $this->labelID || !empty($this->participants)) {
			$conversationList = new UserConversationList(WCF::getUser()->userID, 'outbox');
			$this->outboxCount = $conversationList->countObjects();
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		WCF::getTPL()->assign([
			'filter' => $this->filter,
			'hasMarkedItems' => ClipboardHandler::getInstance()->hasMarkedItems(ClipboardHandler::getInstance()->getObjectTypeID('com.woltlab.wcf.conversation.conversation')),
			'labelID' => $this->labelID,
			'labelList' => $this->labelList,
			'conversationCount' => $this->conversationCount,
			'draftCount' => $this->draftCount,
			'hiddenCount' => $this->hiddenCount,
			'outboxCount' => $this->outboxCount,
			'participants' => $this->participants
		]);
	}
}
