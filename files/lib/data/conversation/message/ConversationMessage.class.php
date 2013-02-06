<?php
namespace wcf\data\conversation\message;
use wcf\data\attachment\GroupedAttachmentList;
use wcf\data\conversation\Conversation;
use wcf\data\DatabaseObject;
use wcf\data\IMessage;
use wcf\system\bbcode\AttachmentBBCode;
use wcf\system\bbcode\MessageParser;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Represents a conversation message.
 * 
 * @author	Marcel Werk
 * @copyright	2009-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation.message
 * @category	Community Framework
 */
class ConversationMessage extends DatabaseObject implements IMessage {
	/**
	 * @see	wcf\data\DatabaseObject::$databaseTableName
	 */
	protected static $databaseTableName = 'conversation_message';
	
	/**
	 * @see	wcf\data\DatabaseObject::$databaseIndexName
	 */
	protected static $databaseTableIndexName = 'messageID';
	
	/**
	 * conversation object
	 * @var	wcf\data\conversation\Conversation
	 */
	protected $conversation = null;
	
	/**
	 * @see	wcf\data\IMessage::getFormattedMessage()
	 */
	public function getFormattedMessage() {
		// assign embedded attachments
		AttachmentBBCode::setObjectID($this->messageID);
		
		// parse and return message
		MessageParser::getInstance()->setOutputType('text/html');
		return MessageParser::getInstance()->parse($this->message, $this->enableSmilies, $this->enableHtml, $this->enableBBCodes);
	}
	
	/**
	 * Gets and assigns embedded attachments.
	 *
	 * @return	wcf\data\attachment\GroupedAttachmentList
	 */
	public function getAttachments() {
		if (MODULE_ATTACHMENT == 1 && $this->attachments) {
			$attachmentList = new GroupedAttachmentList('com.woltlab.wcf.conversation.message');
			$attachmentList->getConditionBuilder()->add('attachment.objectID IN (?)', array($this->messageID));
			$attachmentList->readObjects();
			$attachmentList->setPermissions(array(
				'canDownload' => true,
				'canViewPreview' => true
			));
				
			// set embedded attachments
			AttachmentBBCode::setAttachmentList($attachmentList);
				
			return $attachmentList;
		}
	
		return null;
	}
	
	/**
	 * Returns an excerpt of this message.
	 * 
	 * @param	string		$maxLength
	 * @return	string
	 */
	public function getExcerpt($maxLength = 255) {
		MessageParser::getInstance()->setOutputType('text/simplified-html');
		$message = MessageParser::getInstance()->parse($this->message, $this->enableSmilies, $this->enableHtml, $this->enableBBCodes);
		
		return StringUtil::truncateHTML($message, $maxLength);
	}
	
	/**
	 * Returns the conversation of this message.
	 * 
	 * @return	wcf\data\conversation\Conversation
	 */
	public function getConversation() {
		if ($this->conversation === null) {
			$this->conversation = new Conversation($this->conversationID);
		}
		
		return $this->conversation;
	}
	
	/**
	 * Sets the conversation of this message.
	 * 
	 * @param	wcf\data\conversation\Conversation	$conversation
	 */
	public function setConversation(Conversation $conversation) {
		if ($this->conversationID == $conversation->conversationID) {
			$this->conversation = $conversation;
		}
	}
	
	/**
	 * Returns true, if current user may edit this message.
	 * 
	 * @return	boolean
	 */
	public function canEdit() {
		return (WCF::getUser()->userID == $this->userID);
	}
	
	/**
	 * @see	wcf\data\IMessage::getMessage()
	 */
	public function getMessage() {
		return $this->message;
	}
	
	/**
	 * @see	wcf\data\ILinkableObject::getLink()
	 */
	public function getLink() {
		return LinkHandler::getInstance()->getLink('Conversation', array(
			'object' => $this->getConversation(),
			'messageID' => $this->messageID
		), '#message'.$this->messageID);
	}
	
	/**
	 * @see	wcf\data\IMessage::getTime()
	 */
	public function getTime() {
		return $this->time;
	}
	
	/**
	 * @see	wcf\data\ITitledObject::getTitle()
	 */
	public function getTitle() {
		if ($this->messageID == $this->getConversation()->firstMessageID) {
			return $this->getConversation()->subject;
		}
		
		return 'RE: '.$this->getConversation()->subject;
	}
	
	/**
	 * @see	wcf\data\IMessage::getUserID()
	 */
	public function getUserID() {
		return $this->userID;
	}
	
	/**
	 * @see	wcf\data\IMessage::getUsername()
	 */
	public function getUsername() {
		return $this->username;
	}
	
	/**
	 * @see	wcf\data\IMessage::__toString()
	 */
	public function __toString() {
		return $this->getFormattedMessage();
	}
}
