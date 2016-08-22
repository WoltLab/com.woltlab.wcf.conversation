<?php
namespace wcf\data\conversation\message;
use wcf\data\attachment\GroupedAttachmentList;
use wcf\data\conversation\Conversation;
use wcf\data\DatabaseObject;
use wcf\data\IMessage;
use wcf\data\TUserContent;
use wcf\system\html\output\HtmlOutputProcessor;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Represents a conversation message.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\Data\Conversation\Message
 *
 * @property-read	integer		$messageID
 * @property-read	integer		$conversationID
 * @property-read	integer|null	$userID
 * @property-read	string		$username
 * @property-read	string		$message
 * @property-read	integer		$time
 * @property-read	integer		$attachments
 * @property-read	integer		$enableHtml
 * @property-read	string		$ipAddress
 * @property-read	integer		$lastEditTime
 * @property-read	integer		$editCount
 * @property-read	integer		$hasEmbeddedObjects
 */
class ConversationMessage extends DatabaseObject implements IMessage {
	use TUserContent;
	
	/**
	 * conversation object
	 * @var	Conversation
	 */
	protected $conversation = null;
	
	/**
	 * @inheritDoc
	 */
	public function getFormattedMessage() {
		$processor = new HtmlOutputProcessor();
		$processor->process($this->message, 'com.woltlab.wcf.conversation.message', $this->messageID);
		
		return $processor->getHtml();
	}
	
	/**
	 * Returns a simplified version of the formatted message.
	 * 
	 * @return	string
	 */
	public function getSimplifiedFormattedMessage() {
		$processor = new HtmlOutputProcessor();
		$processor->setOutputType('text/simplified-html');
		$processor->process($this->message, 'com.woltlab.wcf.conversation.message', $this->messageID);
		
		return $processor->getHtml();
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
	 * @inheritDoc
	 */
	public function getExcerpt($maxLength = 255) {
		return StringUtil::truncateHTML($this->getSimplifiedFormattedMessage(), $maxLength);
	}
	
	/**
	 * Returns a version of this message optimized for use in emails.
	 * 
	 * @param	string	$mimeType	Either 'text/plain' or 'text/html'
	 * @return	string
	 */
	public function getMailText($mimeType = 'text/plain') {
		switch ($mimeType) {
			case 'text/plain':
				$processor = new HtmlOutputProcessor();
				$processor->setOutputType('text/plain');
				$processor->process($this->message, 'com.woltlab.wcf.conversation.message', $this->messageID);
				
				return $processor->getHtml();
			case 'text/html':
				return $this->getSimplifiedFormattedMessage();
		}
		
		throw new \LogicException('Unreachable');
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
	 * @inheritDoc
	 */
	public function getMessage() {
		return $this->message;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getLink() {
		return LinkHandler::getInstance()->getLink('Conversation', [
			'object' => $this->getConversation(),
			'messageID' => $this->messageID
		], '#message'.$this->messageID);
	}
	
	/**
	 * @inheritDoc
	 */
	public function getTitle() {
		if ($this->messageID == $this->getConversation()->firstMessageID) {
			return $this->getConversation()->subject;
		}
		
		return 'RE: '.$this->getConversation()->subject;
	}
	
	/**
	 * @inheritDoc
	 */
	public function isVisible() {
		return true;
	}
	
	/**
	 * @inheritDoc
	 */
	public function __toString() {
		return $this->getFormattedMessage();
	}
}
