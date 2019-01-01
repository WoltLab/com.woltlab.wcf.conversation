<?php
namespace wcf\system\event\listener;
use wcf\acp\form\UserGroupAddForm;
use wcf\acp\form\UserGroupEditForm;
use wcf\data\user\group\UserGroup;
use wcf\system\WCF;

/**
 * Handles 'canBeAddedAsParticipant' setting.
 *
 * @author	Marcel Werk
 * @copyright	2001-2019 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\Event\Listener
 */
class UserGroupAddCanBeAddedAsParticipantListener implements IParameterizedEventListener {
	/**
	 * instance of UserGroupAddForm
	 * @var	UserGroupAddForm|UserGroupEditForm
	 */
	protected $eventObj;
	
	/**
	 * true if group can be added as participant
	 * @var	boolean
	 */
	protected $canBeAddedAsParticipant = 0;
	
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		$this->eventObj = $eventObj;
		
		if ($this->eventObj instanceof UserGroupEditForm && is_object($this->eventObj->group)) {
			switch ($this->eventObj->group->groupType) {
				case UserGroup::EVERYONE:
				case UserGroup::GUESTS:
				case UserGroup::USERS:
					return;
			}
		}
		
		$this->$eventName();
	}
	
	/**
	 * Handles the assignVariables event.
	 */
	protected function assignVariables() {
		WCF::getTPL()->assign([
			'canBeAddedAsParticipant' => $this->canBeAddedAsParticipant
		]);
	}
	
	/**
	 * Handles the readData event.
	 * This is only called in UserGroupEditForm.
	 */
	protected function readData() {
		if (empty($_POST)) {
			$this->canBeAddedAsParticipant = $this->eventObj->group->canBeAddedAsParticipant;
		}
	}
	
	/**
	 * Handles the readFormParameters event.
	 */
	protected function readFormParameters() {
		if (isset($_POST['canBeAddedAsParticipant'])) $this->canBeAddedAsParticipant = intval($_POST['canBeAddedAsParticipant']);
	}
	
	/**
	 * Handles the save event.
	 */
	protected function save() {
		$this->eventObj->additionalFields = array_merge($this->eventObj->additionalFields, [
			'canBeAddedAsParticipant' => $this->canBeAddedAsParticipant
		]);
	}
}
