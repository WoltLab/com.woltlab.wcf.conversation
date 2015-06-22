<?php
namespace wcf\system\attachment;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\Conversation;
use wcf\system\WCF;
use wcf\util\ArrayUtil;

/**
 * Attachment object type implementation for conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2009-2014 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.attachment
 * @category	Community Framework
 */
class ConversationMessageAttachmentObjectType extends AbstractAttachmentObjectType {
	/**
	 * @see	\wcf\system\attachment\IAttachmentObjectType::getMaxSize()
	 */
	public function getMaxSize() {
		return WCF::getSession()->getPermission('user.conversation.maxAttachmentSize');
	}
	
	/**
	 * @see	\wcf\system\attachment\IAttachmentObjectType::getAllowedExtensions()
	 */
	public function getAllowedExtensions() {
		return ArrayUtil::trim(explode("\n", WCF::getSession()->getPermission('user.conversation.allowedAttachmentExtensions')));
	}
	
	/**
	 * @see	\wcf\system\attachment\IAttachmentObjectType::getMaxCount()
	 */
	public function getMaxCount() {
		return WCF::getSession()->getPermission('user.conversation.maxAttachmentCount');
	}
	
	/**
	 * @see	\wcf\system\attachment\IAttachmentObjectType::canDownload()
	 */
	public function canDownload($objectID) {
		if ($objectID) {
			$message = new ConversationMessage($objectID);
			$conversation = Conversation::getUserConversation($message->conversationID, WCF::getUser()->userID);
			if ($conversation->canRead()) return true;
		}
		
		return false;
	}
	
	/**
	 * @see	\wcf\system\attachment\IAttachmentObjectType::canUpload()
	 */
	public function canUpload($objectID, $parentObjectID = 0) {
		if (!WCF::getSession()->getPermission('user.conversation.canUploadAttachment')) {
			return false;
		}
		
		if ($objectID) {
			$message = new ConversationMessage($objectID);
			if ($message->userID == WCF::getUser()->userID) return true;
			return false;
		}
		
		return true;
	}
	
	/**
	 * @see	\wcf\system\attachment\IAttachmentObjectType::canDelete()
	 */
	public function canDelete($objectID) {
		if ($objectID) {
			$message = new ConversationMessage($objectID);
			if ($message->userID == WCF::getUser()->userID) return true;
		}
		
		return false;
	}
}
