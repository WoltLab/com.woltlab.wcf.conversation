<?php
namespace wcf\system\importer;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationEditor;

/**
 * Imports conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.importer
 * @category	Community Framework
 */
class ConversationImporter extends AbstractImporter {
	/**
	 * @see	\wcf\system\importer\AbstractImporter::$className
	 */
	protected $className = 'wcf\data\conversation\Conversation';
	
	/**
	 * @see	\wcf\system\importer\IImporter::import()
	 */
	public function import($oldID, array $data, array $additionalData = []) {
		$oldUserID = $data['userID'];
		$data['userID'] = ImportHandler::getInstance()->getNewID('com.woltlab.wcf.user', $data['userID']);
		
		// check existing conversation
		if (is_numeric($oldID)) {
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
