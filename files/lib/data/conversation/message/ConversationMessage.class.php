<?php
namespace wcf\data\conversation\message;
use wcf\data\conversation\Conversation;
use wcf\data\DatabaseObject;
use wcf\system\bbcode\MessageParser;
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
 * @category 	Community Framework
 */
class ConversationMessage extends DatabaseObject {
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
	 * @var wcf\data\conversation\Conversation
	 */
	protected $conversation = null;
	
	/**
	 * Returns the formatted text of this message.
	 *
	 * @return string
	 */
	public function getFormattedMessage() {
		MessageParser::getInstance()->setOutputType('text/html');
		return MessageParser::getInstance()->parse($this->message, $this->enableSmilies, $this->enableHtml, $this->enableBBCodes);
	}
	
	/**
	 * Returns an excerpt of this message.
	 *
	 * @param	string		$maxLength
	 * @return	string
	 */
	public function getExcerpt($maxLength = 255) {
		MessageParser::getInstance()->setOutputType('text/plain');
		$message = MessageParser::getInstance()->parse($this->message, $this->enableSmilies, $this->enableHtml, $this->enableBBCodes);
		if (StringUtil::length($message) > $maxLength) {
			$message = StringUtil::encodeHTML(StringUtil::substring($message, 0, $maxLength)).'&hellip;';
		}
		else {
			$message = StringUtil::encodeHTML($message);
		}
		
		return $message;
	}
	
	/**
	 * Returns the conversation of this message.
	 * 
	 * @return wcf\data\conversation\Conversation
	 */
	public function getConversation() {
		if ($this->conversation === null) {
			$this->conversation = new Conversation($this->conversationID);
		}
		
		return $this->conversation;
	}
	
	/**
	 * Returns true, if current user may edit this message.
	 * 
	 * @return	boolean
	 */
	public function canEdit() {
		return (WCF::getUser()->userID == $this->userID);
	}
}

