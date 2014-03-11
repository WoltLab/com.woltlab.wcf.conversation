<?php
namespace wcf\system\importer;
use wcf\system\WCF;

/**
 * Imports conversation users.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2014 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.importer
 * @category	Community Framework
 */
class ConversationUserImporter extends AbstractImporter {
	/**
	 * @see	\wcf\system\importer\IImporter::import()
	 */
	public function import($oldID, array $data, array $additionalData = array()) {
		$data['conversationID'] = ImportHandler::getInstance()->getNewID('com.woltlab.wcf.conversation', $data['conversationID']);
		if (!$data['conversationID']) return 0;
		$data['participantID'] = ImportHandler::getInstance()->getNewID('com.woltlab.wcf.user', $data['participantID']);
		
		$sql = "INSERT INTO			wcf".WCF_N."_conversation_to_user
							(conversationID, participantID, username, hideConversation, isInvisible, lastVisitTime)
			VALUES				(?, ?, ?, ?, ?, ?)
			ON DUPLICATE KEY UPDATE		hideConversation = IF(hideConversation > 0 AND hideConversation = VALUES(hideConversation),hideConversation,0),
							isInvisible = IF(isInvisible AND VALUES(isInvisible),1,0),
							lastVisitTime = GREATEST(lastVisitTime,VALUES(lastVisitTime))";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(
			$data['conversationID'],
			$data['participantID'],
			$data['username'],
			$data['hideConversation'],
			$data['isInvisible'],
			$data['lastVisitTime']
		));
		
		// save labels
		if ($data['participantID'] && !empty($additionalData['labelIDs'])) {
			$sql = "INSERT IGNORE INTO		wcf".WCF_N."_conversation_label_to_object
								(labelID, conversationID)
				VALUES				(?, ?)";
			$statement = WCF::getDB()->prepareStatement($sql);
			foreach ($additionalData['labelIDs'] as $labelID) {
				$labelID = ImportHandler::getInstance()->getNewID('com.woltlab.wcf.conversation.label', $labelID);
				if ($labelID) $statement->execute(array($labelID, $data['conversationID']));
			}
		}
		
		return 1;
	}
}
