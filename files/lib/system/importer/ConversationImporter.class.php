<?php
namespace wcf\system\importer;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationEditor;

/**
 * Imports conversations.
 *
 * @author	Marcel Werk
 * @copyright	2001-2013 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.importer
 * @category	Community Framework
 */
class ConversationImporter implements IImporter {
	/**
	 * @see wcf\system\importer\IImporter::import()
	 */
	public function import($oldID, array $data) {
		$data['userID'] = ImportHandler::getInstance()->getNewID('com.woltlab.wcf.user', $data['userID']);
		
		// check existing conversation
		$existingConversation = new Conversation($oldID);
		if (!$existingConversation->conversationID) $data['conversationID'] = $oldID;
		
		$conversation = ConversationEditor::create($data);
		
		ImportHandler::getInstance()->saveNewID('com.woltlab.wcf.conversation', $oldID, $conversation->conversationID);
		
		return $conversation->conversationID;
	}
}
