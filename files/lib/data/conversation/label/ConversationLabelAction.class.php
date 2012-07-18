<?php
namespace wcf\data\conversation\label;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Executes label-related actions.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation.label
 * @category 	Community Framework
 */
class ConversationLabelAction extends AbstractDatabaseObjectAction {
	/**
	 * @see wcf\data\AbstractDatabaseObjectAction::$className
	 */
	protected $className = 'wcf\data\conversation\label\ConversationLabelEditor';
	
	/**
	 * Validates parameters to add a new label.
	 */
	public function validateAdd() {
		if (!WCF::getSession()->getPermission('user.conversation.canUseConversation')) {
			throw new PermissionDeniedException();
		}
		
		$this->parameters['data']['labelName'] = (isset($this->parameters['data']['labelName'])) ? StringUtil::trim($this->parameters['data']['labelName']) : '';
		if (empty($this->parameters['data']['labelName'])) {
			throw new UserInputException('labelName');
		}
		
		$this->parameters['data']['cssClassName'] = (isset($this->parameters['data']['cssClassName'])) ? StringUtil::trim($this->parameters['cssClassName']) : '';
		if (empty($this->parameters['data']['cssClassName']) || !in_array($this->parameters['data']['cssClassName'], ConversationLabel::getLabelCssClassNames())) {
			throw new UserInputException('cssClassName');
		}
		
		// 'none' is a pseudo value
		if ($this->parameters['data']['cssClassName'] == 'none') $this->parameters['data']['cssClassName'] = '';
	}
	
	/**
	 * Adds a new user-specific label.
	 * 
	 * @return	array
	 */
	public function add() {
		$label = ConversationLabelEditor::create(array(
			'userID' => WCF::getUser()->userID,
			'label' => $this->parameters['data']['labelName'],
			'cssClassName' => $this->parameters['data']['cssClassName']
		));
		
		return array(
			'actionName' => 'add',
			'cssClassName' => $label->cssClassName,
			'label' => $label->label,
			'labelID' => $label->labelID
		);
	}
}
