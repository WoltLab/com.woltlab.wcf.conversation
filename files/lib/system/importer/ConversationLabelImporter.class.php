<?php
namespace wcf\system\importer;
use wcf\data\conversation\label\ConversationLabelAction;

/**
 * Imports conversation labels.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2015 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.importer
 * @category	Community Framework
 */
class ConversationLabelImporter extends AbstractImporter {
	/**
	 * @see	\wcf\system\importer\AbstractImporter::$className
	 */
	protected $className = 'wcf\data\conversation\label\ConversationLabel';
	
	/**
	 * @see	\wcf\system\importer\IImporter::import()
	 */
	public function import($oldID, array $data, array $additionalData = []) {
		$data['userID'] = ImportHandler::getInstance()->getNewID('com.woltlab.wcf.user', $data['userID']);
		if (!$data['userID']) return 0;
		
		$action = new ConversationLabelAction([], 'create', [
			'data' => $data
		]);
		$returnValues = $action->executeAction();
		$newID = $returnValues['returnValues']->labelID;
		
		ImportHandler::getInstance()->saveNewID('com.woltlab.wcf.conversation.label', $oldID, $newID);
		
		return $newID;
	}
}
