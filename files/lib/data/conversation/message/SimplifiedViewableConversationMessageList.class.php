<?php
namespace wcf\data\conversation\message;

/**
 * Represents a simplified version of ViewableConversationMessageList.
 * Disables the loading of attachments and embedded objects by default.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation.message
 * @category	Community Framework
 */
class SimplifiedViewableConversationMessageList extends ViewableConversationMessageList {
	/**
	 * @see	\wcf\data\conversation\message\ViewableConversationMessageList::$attachmentLoading
	 */
	protected $attachmentLoading = false;
	
	/**
	 * @see	\wcf\data\conversation\message\ViewableConversationMessageList::$embeddedObjectLoading
	 */
	protected $embeddedObjectLoading = false;
}
