<?php
namespace wcf\system\importer;
use wcf\data\conversation\label\ConversationLabelAction;

/**
 * Imports conversation labels.
 *
 * @author	Marcel Werk
 * @copyright	2001-2013 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.importer
 * @category	Community Framework
 */
class ConversationLabelImporter implements IImporter {
	/**
	 * @see wcf\system\importer\IImporter::import()
	 */
	public function import($oldID, array $data) {
		$data['userID'] = ImportHandler::getInstance()->getNewID('com.woltlab.wcf.user', $data['userID']);
		if (!$data['userID']) return 0;
		
		$action = new ConversationLabelAction(array(), 'create', array(
			'data' => $data		
		));
		$returnValues = $action->executeAction();
		$newID = $returnValues['returnValues']->labelID;
		
		ImportHandler::getInstance()->saveNewID('com.woltlab.wcf.conversation.label', $oldID, $newID);
		
		return $newID;
	}
}
