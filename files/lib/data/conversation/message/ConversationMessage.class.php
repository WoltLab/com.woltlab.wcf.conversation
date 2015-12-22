<?php
namespace wcf\data\conversation\message;
use wcf\data\attachment\GroupedAttachmentList;
use wcf\data\conversation\Conversation;
use wcf\data\DatabaseObject;
use wcf\data\IMessage;
use wcf\data\TUserContent;
use wcf\system\bbcode\MessageParser;
use wcf\system\message\embedded\object\MessageEmbeddedObjectManager;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Represents a conversation message.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2015 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation.message
 * @category	Community Framework
 */
class ConversationMessage extends DatabaseObject implements IMessage {
	use TUserContent;
	
	/**
	 * @inheritdoc
	 */
	protected static $databaseTableName = 'conversation_message';
	
	/**
	 * @inheritdoc
	 */
	protected static $databaseTableIndexName = 'messageID';
	
	/**
	 * conversation object
	 * @var	Conversation
	 */
	protected $conversation = null;
	
	/**
	 * @inheritdoc
	 */
	public function getFormattedMessage() {
		// assign embedded objects
		MessageEmbeddedObjectManager::getInstance()->setActiveMessage('com.woltlab.wcf.conversation.message', $this->messageID);
		
		// parse and return message
		MessageParser::getInstance()->setOutputType('text/html');
		return MessageParser::getInstance()->parse($this->message, $this->enableSmilies, $this->enableHtml, $this->enableBBCodes);
	}
	
	/**
	 * Returns a simplified version of the formatted message.
	 * 
	 * @return	string
	 */
	public function getSimplifiedFormattedMessage() {
		MessageParser::getInstance()->setOutputType('text/simplified-html');
		return MessageParser::getInstance()->parse($this->message, $this->enableSmilies, $this->enableHtml, $this->enableBBCodes);
	}
	
	/**
	 * Assigns and returns the embedded attachments.
	 * 
	 * @param	boolean		$ignoreCache
	 * @return	GroupedAttachmentList
	 */
	public function getAttachments($ignoreCache = false) {
		if (MODULE_ATTACHMENT == 1 && ($this->attachments || $ignoreCache)) {
			$attachmentList = new GroupedAttachmentList('com.woltlab.wcf.conversation.message');
			$attachmentList->getConditionBuilder()->add('attachment.objectID IN (?)', [$this->messageID]);
			$attachmentList->readObjects();
			$attachmentList->setPermissions([
				'canDownload' => true,
				'canViewPreview' => true
			]);
			
			if ($ignoreCache && !count($attachmentList)) {
				return null;
			}
			
			return $attachmentList;
		}
		
		return null;
	}
	
	/**
	 * @inheritdoc
	 */
	public function getExcerpt($maxLength = 255) {
		return StringUtil::truncateHTML($this->getSimplifiedFormattedMessage(), $maxLength);
	}
	
	/**
	 * Returns a text-only version of this message.
	 * 
	 * @return	string
	 */
	public function getMailText() {
		MessageParser::getInstance()->setOutputType('text/simplified-html');
		$message = MessageParser::getInstance()->parse($this->message, $this->enableSmilies, $this->enableHtml, $this->enableBBCodes);
		
		return MessageParser::getInstance()->stripHTML($message);
	}
	
	/**
	 * Returns the conversation of this message.
	 * 
	 * @return	Conversation
	 */
	public function getConversation() {
		if ($this->conversation === null) {
			$this->conversation = Conversation::getUserConversation($this->conversationID, WCF::getUser()->userID);
		}
		
		return $this->conversation;
	}
	
	/**
	 * Sets the conversation of this message.
	 * 
	 * @param	Conversation	$conversation
	 */
	public function setConversation(Conversation $conversation) {
		if ($this->conversationID == $conversation->conversationID) {
			$this->conversation = $conversation;
		}
	}
	
	/**
	 * Returns true if current user may edit this message.
	 * 
	 * @return	boolean
	 */
	public function canEdit() {
		return (WCF::getUser()->userID == $this->userID && ($this->getConversation()->isDraft || WCF::getSession()->getPermission('user.conversation.canEditMessage')));
	}
	
	/**
	 * @inheritdoc
	 */
	public function getMessage() {
		return $this->message;
	}
	
	/**
	 * @inheritdoc
	 */
	public function getLink() {
		return LinkHandler::getInstance()->getLink('Conversation', [
			'object' => $this->getConversation(),
			'messageID' => $this->messageID
		], '#message'.$this->messageID);
	}
	
	/**
	 * @inheritdoc
	 */
	public function getTitle() {
		if ($this->messageID == $this->getConversation()->firstMessageID) {
			return $this->getConversation()->subject;
		}
		
		return 'RE: '.$this->getConversation()->subject;
	}
	
	/**
	 * @inheritdoc
	 */
	public function isVisible() {
		return true;
	}
	
	/**
	 * @inheritdoc
	 */
	public function __toString() {
		return $this->getFormattedMessage();
	}
}
