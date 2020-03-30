<?php
namespace wcf\system\importer;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationEditor;

/**
 * Imports conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2019 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\Importer
 */
class ConversationImporter extends AbstractImporter {
	/**
	 * @inheritDoc
	 */
	protected $className = Conversation::class;
	
	/**
	 * @inheritDoc
	 */
	public function import($oldID, array $data, array $additionalData = []) {
		$oldUserID = $data['userID'];
		$data['userID'] = ImportHandler::getInstance()->getNewID('com.woltlab.wcf.user', $data['userID']);
		
		// check existing conversation
		if (ctype_digit((string)$oldID)) {
			$existingConversation = new Conversation($oldID);
			if (!$existingConversation->conversationID) $data['conversationID'] = $oldID;
		}
		
		$conversation = ConversationEditor::create($data);
		
		ImportHandler::getInstance()->saveNewID('com.woltlab.wcf.conversation', $oldID, $conversation->conversationID);
		
		// add author
		if (empty($data['isDraft'])) {
			ImportHandler::getInstance()->getImporter('com.woltlab.wcf.conversation.user')->import(0, [
				'conversationID' => $oldID,
				'participantID' => $oldUserID,
				'username' => $data['username'],
				'hideConversation' => 0,
				'isInvisible' => 0,
				'lastVisitTime' => $data['time']
			], ['labelIDs' => []]);
		}
		
		return $conversation->conversationID;
	}
}
