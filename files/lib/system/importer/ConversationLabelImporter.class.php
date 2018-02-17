<?php
namespace wcf\system\importer;
use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\label\ConversationLabelAction;

/**
 * Imports conversation labels.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2018 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\Importer
 */
class ConversationLabelImporter extends AbstractImporter {
	/**
	 * @inheritDoc
	 */
	protected $className = ConversationLabel::class;
	
	/**
	 * @inheritDoc
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
