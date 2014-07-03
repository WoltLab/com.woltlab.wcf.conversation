<?php
namespace wcf\data\conversation\message;
use wcf\data\attachment\GroupedAttachmentList;
use wcf\data\conversation\Conversation;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\message\embedded\object\MessageEmbeddedObjectManager;

/**
 * Represents a list of viewable conversation messages.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2014 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation.message
 * @category	Community Framework
 */
class ViewableConversationMessageList extends ConversationMessageList {
	/**
	 * @see	\wcf\data\DatabaseObjectList::$sqlOrderBy
	 */
	public $sqlOrderBy = 'conversation_message.time';
	
	/**
	 * @see	\wcf\data\DatabaseObjectList::$decoratorClassName
	 */
	public $decoratorClassName = 'wcf\data\conversation\message\ViewableConversationMessage';
	
	/**
	 * attachment object ids
	 * @var	array<integer>
	 */
	public $attachmentObjectIDs = array();
	
	/**
	 * ids of the messages with embedded objects
	 * @var array<integer>
	 */
	public $embeddedObjectMessageIDs = array();
	
	/**
	 * attachment list
	 * @var	\wcf\data\attachment\GroupedAttachmentList
	 */
	protected $attachmentList = null;
	
	/**
	 * max post time
	 * @var	integer
	 */
	protected $maxPostTime = 0;
	
	/**
	 * enables/disables the loading of attachments
	 * @var boolean
	 */
	protected $attachmentLoading = true;
	
	/**
	 * enables/disables the loading of embedded objects
	 * @var boolean
	 */
	protected $embeddedObjectLoading = true;
	
	/**
	 * conversation object
	 * @var \wcf\data\conversation\Conversation
	 */
	protected $conversation = null;
	
	/**
	 * Creates a new ViewableConversationMessageList object.
	 */
	public function __construct() {
		parent::__construct();
		
		$this->sqlSelects .= "user_option_value.*, user_table.*";
		$this->sqlJoins .= " LEFT JOIN wcf".WCF_N."_user user_table ON (user_table.userID = conversation_message.userID)";
		$this->sqlJoins .= " LEFT JOIN wcf".WCF_N."_user_option_value user_option_value ON (user_option_value.userID = user_table.userID)";
		
		// get avatars
		if (!empty($this->sqlSelects)) $this->sqlSelects .= ',';
		$this->sqlSelects .= "user_avatar.*";
		$this->sqlJoins .= " LEFT JOIN wcf".WCF_N."_user_avatar user_avatar ON (user_avatar.avatarID = user_table.avatarID)";
	}
	
	/**
	 * @see	\wcf\data\DatabaseObjectList::readObjects()
	 */
	public function readObjects() {
		if ($this->objectIDs === null) {
			$this->readObjectIDs();
		}
		
		parent::readObjects();
		
		foreach ($this->objects as $message) {
			if ($message->time > $this->maxPostTime) {
				$this->maxPostTime = $message->time;
			}
			if ($this->conversation !== null) {
				$message->setConversation($this->conversation);
			}
			
			if ($message->attachments) {
				$this->attachmentObjectIDs[] = $message->messageID;
			}
			
			if ($message->hasEmbeddedObjects) {
				$this->embeddedObjectMessageIDs[] = $message->messageID;
			}
		}
		
		if ($this->embeddedObjectLoading) {
			$this->readEmbeddedObjects();
		}
		if ($this->attachmentLoading) {
			$this->readAttachments();
		}
	}
	
	/**
	 * Reads the embedded objects of the messages in the list.
	 */
	public function readEmbeddedObjects() {
		if (!empty($this->embeddedObjectMessageIDs)) {
			// add message objects to attachment object cache to save SQL queries
			ObjectTypeCache::getInstance()->getObjectTypeByName('com.woltlab.wcf.attachment.objectType', 'com.woltlab.wcf.conversation.message')->getProcessor()->setCachedObjects($this->objects);
				
			// load embedded objects
			MessageEmbeddedObjectManager::getInstance()->loadObjects('com.woltlab.wcf.conversation.message', $this->embeddedObjectMessageIDs);
		}
	}
	
	/**
	 * Reads the list of attachments.
	 */
	public function readAttachments() {
		if (MODULE_ATTACHMENT == 1 && !empty($this->attachmentObjectIDs)) {
			$this->attachmentList = new GroupedAttachmentList('com.woltlab.wcf.conversation.message');
			$this->attachmentList->getConditionBuilder()->add('attachment.objectID IN (?)', array($this->attachmentObjectIDs));
			$this->attachmentList->readObjects();
		}
	}
	
	/**
	 * Returns the max post time.
	 * 
	 * @return	integer
	 */
	public function getMaxPostTime() {
		return $this->maxPostTime;
	}
	
	/**
	 * Returns the list of attachments.
	 * 
	 * @return	\wcf\data\attachment\GroupedAttachmentList
	 */
	public function getAttachmentList() {
		return $this->attachmentList;
	}
	
	/**
	 * Enables/disables the loading of attachments.
	 *
	 * @param	boolean		$enable
	 */
	public function enableAttachmentLoading($enable = true) {
		$this->attachmentLoading = $enable;
	}
	
	/**
	 * Enables/disables the loading of embedded objects.
	 *
	 * @param	boolean		$enable
	 */
	public function enableEmbeddedObjectLoading($enable = true) {
		$this->embeddedObjectLoading = $enable;
	}
	
	/**
	 * Sets active conversation.
	 *
	 * @param	\wcf\data\conversation\Conversation		$conversation
	 */
	public function setConversation(Conversation $conversation) {
		$this->conversation = $conversation;
	}
}
