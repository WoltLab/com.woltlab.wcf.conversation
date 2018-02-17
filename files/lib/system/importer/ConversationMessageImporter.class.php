<?php
namespace wcf\system\importer;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\message\ConversationMessageEditor;

/**
 * Imports conversation messages.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2018 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\Importer
 */
class ConversationMessageImporter extends AbstractImporter {
	/**
	 * @inheritDoc
	 */
	protected $className = ConversationMessage::class;
	
	/**
	 * @inheritDoc
	 */
	public function import($oldID, array $data, array $additionalData = []) {
		$data['conversationID'] = ImportHandler::getInstance()->getNewID('com.woltlab.wcf.conversation', $data['conversationID']);
		if (!$data['conversationID']) return 0;
		$data['userID'] = ImportHandler::getInstance()->getNewID('com.woltlab.wcf.user', $data['userID']);
		
		// check existing message
		if (is_numeric($oldID)) {
			$existingMessage = new ConversationMessage($oldID);
			if (!$existingMessage->messageID) $data['messageID'] = $oldID;
		}
		
		$message = ConversationMessageEditor::create($data);
		
		ImportHandler::getInstance()->saveNewID('com.woltlab.wcf.conversation.message', $oldID, $message->messageID);
		
		return $message->messageID;
	}
}
