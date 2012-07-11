<?php
namespace wcf\data\conversation\message;
use wcf\data\attachment\GroupedAttachmentList;

/**
 * Represents a list of conversation messages.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation.message
 * @category 	Community Framework
 */
class ViewableConversationMessageList extends ConversationMessageList {
	/**
	 * @see	wcf\data\DatabaseObjectList::$sqlOrderBy
	 */
	public $sqlOrderBy = 'conversation_message.time';
	
	/**
	 * decorator class name
	 * @var string
	 */
	public $decoratorClassName = 'wcf\data\conversation\message\ViewableConversationMessage';
	
	/**
	 * attachment object ids
	 * @var array<integer>
	 */
	public $attachmentObjectIDs = array();
	
	/**
	 * attachment list
	 * @var wcf\data\attachment\GroupedAttachmentList
	 */
	protected $attachmentList = null;
	
	/**
	 * max post time
	 * @var integer
	 */
	protected $maxPostTime = 0;
	
	/**
	 * Creates a new ViewableConversationMessageList object.
	 */
	public function __construct() {
		parent::__construct();
		
		$this->sqlSelects .= "user_table.*";
		$this->sqlJoins .= " LEFT JOIN wcf".WCF_N."_user user_table ON (user_table.userID = conversation_message.userID)";
		
		// get avatars
		if (!empty($this->sqlSelects)) $this->sqlSelects .= ',';
		$this->sqlSelects .= "user_avatar.*";
		$this->sqlJoins .= " LEFT JOIN wcf".WCF_N."_user_avatar user_avatar ON (user_avatar.avatarID = user_table.avatarID)";
	}
	
	/**
	 * @see	wcf\data\DatabaseObjectList::readObjects()
	 */
	public function readObjects() {
		if ($this->objectIDs === null) $this->readObjectIDs();
		parent::readObjects();
		
		$messageIDs = array();
		foreach ($this->objects as &$message) {
			if ($message->time > $this->maxPostTime) $this->maxPostTime = $message->time;
			$message = new $this->decoratorClassName($message);
			
			if ($message->attachments) {
				$this->attachmentObjectIDs[] = $message->messageID;
			}
		}
		
		$this->readAttachments();
	}
	
	/**
	 * Gets a list of attachments.
	 */
	public function readAttachments() {
		if (MODULE_ATTACHMENT == 1 && count($this->attachmentObjectIDs)) {
			$this->attachmentList = new GroupedAttachmentList('com.woltlab.wcf.conversation.message');
			$this->attachmentList->getConditionBuilder()->add('attachment.objectID IN (?)', array($this->attachmentObjectIDs));
			$this->attachmentList->readObjects();
		}
	}
	
	/**
	 * Returns the max post time
	 * 
	 * @return integer
	 */
	public function getMaxPostTime() {
		return $this->maxPostTime;
	}
	
	/**
	 * Returns the list of attachments
	 * 
	 * @var wcf\data\attachment\GroupedAttachmentList
	 */
	public function getAttachmentList() {
		return $this->attachmentList;
	}
}
