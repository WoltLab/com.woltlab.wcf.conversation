<?php
namespace wcf\data\conversation\label;
use wcf\data\conversation\Conversation;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\clipboard\ClipboardHandler;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;
use wcf\util\ArrayUtil;
use wcf\util\StringUtil;

/**
 * Executes label-related actions.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\Data\Conversation\Label
 * 
 * @method	ConversationLabel		create()
 * @method	ConversationLabelEditor[]	getObjects()
 * @method	ConversationLabelEditor		getSingleObject()
 */
class ConversationLabelAction extends AbstractDatabaseObjectAction {
	/**
	 * @inheritDoc
	 */
	protected $className = ConversationLabelEditor::class;
	
	/**
	 * @inheritDoc
	 */
	protected $permissionsDelete = ['user.conversation.canUseConversation'];
	
	/**
	 * @inheritDoc
	 */
	protected $permissionsUpdate = ['user.conversation.canUseConversation'];
	
	/**
	 * conversation object
	 * @var	Conversation
	 */
	public $conversation;
	
	/**
	 * conversation label list object
	 * @var	ConversationLabelList
	 */
	public $labelList;
	
	/**
	 * @inheritDoc
	 */
	public function validateUpdate() {
		parent::validateUpdate();
		
		if (count($this->objects) != 1) {
			throw new UserInputException('objectID');
		}
		
		$label = current($this->objects);
		if ($label->userID != WCF::getUser()->userID) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function validateDelete() {
		parent::validateDelete();
		
		if (count($this->objects) != 1) {
			throw new UserInputException('objectID');
		}
		
		$label = current($this->objects);
		if ($label->userID != WCF::getUser()->userID) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * Validates parameters to add a new label.
	 */
	public function validateAdd() {
		if (!WCF::getSession()->getPermission('user.conversation.canUseConversation')) {
			throw new PermissionDeniedException();
		}
		
		// check if user has already created maximum number of labels
		if (count(ConversationLabel::getLabelsByUser()) >= WCF::getSession()->getPermission('user.conversation.maxLabels')) {
			throw new PermissionDeniedException();
		}
		
		$this->readString('labelName', false, 'data');
		$this->readString('cssClassName', false, 'data');
		if (!in_array($this->parameters['data']['cssClassName'], ConversationLabel::getLabelCssClassNames())) {
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
		$label = ConversationLabelEditor::create([
			'userID' => WCF::getUser()->userID,
			'label' => $this->parameters['data']['labelName'],
			'cssClassName' => $this->parameters['data']['cssClassName']
		]);
		
		return [
			'actionName' => 'add',
			'cssClassName' => $label->cssClassName,
			'label' => StringUtil::encodeHTML($label->label),
			'labelID' => $label->labelID
		];
	}
	
	/**
	 * Validates parameters for label assignment form.
	 */
	public function validateGetLabelForm() {
		if (!WCF::getSession()->getPermission('user.conversation.canUseConversation')) {
			throw new PermissionDeniedException();
		}
		
		// validate conversation id
		$this->parameters['conversationIDs'] = isset($this->parameters['conversationIDs']) ? ArrayUtil::toIntegerArray($this->parameters['conversationIDs']) : [];
		if (empty($this->parameters['conversationIDs'])) {
			throw new UserInputException('conversationID');
		}
		
		if (!Conversation::isParticipant($this->parameters['conversationIDs'])) {
			throw new PermissionDeniedException();
		}
		
		// validate available labels
		$this->labelList = ConversationLabel::getLabelsByUser();
		if (!count($this->labelList)) {
			throw new IllegalLinkException();
		}
	}
	
	/**
	 * Returns the label assignment form.
	 * 
	 * @return	array
	 */
	public function getLabelForm() {
		// read assigned labels
		$labelIDs = [];
		foreach ($this->labelList as $label) {
			$labelIDs[] = $label->labelID;
		}
		
		$assignedLabels = [];
		// read assigned labels if editing single conversation
		if (count($this->parameters['conversationIDs']) == 1) {
			$conversationID = current($this->parameters['conversationIDs']);
			
			$conditions = new PreparedStatementConditionBuilder();
			$conditions->add("conversationID = ?", [$conversationID]);
			$conditions->add("labelID IN (?)", [$labelIDs]);
			
			$sql = "SELECT	labelID
				FROM	wcf".WCF_N."_conversation_label_to_object
				".$conditions;
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute($conditions->getParameters());
			$assignedLabels = $statement->fetchAll(\PDO::FETCH_COLUMN);
		}
		
		WCF::getTPL()->assign([
			'assignedLabels' => $assignedLabels,
			'conversation' => $this->conversation,
			'labelList' => $this->labelList
		]);
		
		return [
			'actionName' => 'getLabelForm',
			'template' => WCF::getTPL()->fetch('conversationLabelAssignment')
		];
	}
	
	/**
	 * Validates parameters to assign labels for a conversation.
	 */
	public function validateAssignLabel() {
		$this->validateGetLabelForm();
		
		// validate given labels
		$this->parameters['labelIDs'] = (isset($this->parameters['labelIDs']) && is_array($this->parameters['labelIDs'])) ? ArrayUtil::toIntegerArray($this->parameters['labelIDs']) : [];
		if (!empty($this->parameters['labelIDs'])) {
			foreach ($this->parameters['labelIDs'] as $labelID) {
				$isValid = false;
				
				foreach ($this->labelList as $label) {
					if ($labelID == $label->labelID) {
						$isValid = true;
						break;
					}
				}
				
				if (!$isValid) {
					throw new UserInputException('labelIDs');
				}
			}
		}
	}
	
	/**
	 * Assigns labels to a conversation.
	 * 
	 * @return	array
	 */
	public function assignLabel() {
		// remove previous labels (if any)
		$labelIDs = [];
		foreach ($this->labelList as $label) {
			$labelIDs[] = $label->labelID;
		}
		
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("conversationID IN (?)", [$this->parameters['conversationIDs']]);
		$conditions->add("labelID IN (?)", [$labelIDs]);
		
		$sql = "DELETE FROM	wcf".WCF_N."_conversation_label_to_object
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditions->getParameters());
		
		// assign label ids
		if (!empty($this->parameters['labelIDs'])) {
			$sql = "INSERT INTO	wcf".WCF_N."_conversation_label_to_object
						(labelID, conversationID)
				VALUES		(?, ?)";
			$statement = WCF::getDB()->prepareStatement($sql);
			
			WCF::getDB()->beginTransaction();
			foreach ($this->parameters['labelIDs'] as $labelID) {
				foreach ($this->parameters['conversationIDs'] as $conversationID) {
					$statement->execute([
						$labelID,
						$conversationID
					]);
				}
			}
			WCF::getDB()->commitTransaction();
			
			if (!empty($this->parameters['conversationIDs'])) {
				ClipboardHandler::getInstance()->unmark($this->parameters['conversationIDs'], ClipboardHandler::getInstance()->getObjectTypeID('com.woltlab.wcf.conversation.conversation'));
			}
		}
		
		return [
			'actionName' => 'assignLabel',
			'labelIDs' => $this->parameters['labelIDs']
		];
	}
}
