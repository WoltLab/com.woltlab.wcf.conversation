<?php

namespace wcf\data\conversation\label;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\conversation\Conversation;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Executes label-related actions.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @method  ConversationLabel       create()
 * @method  ConversationLabelEditor[]   getObjects()
 * @method  ConversationLabelEditor     getSingleObject()
 */
class ConversationLabelAction extends AbstractDatabaseObjectAction
{
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
     * @var Conversation
     */
    public $conversation;

    /**
     * conversation label list object
     * @var ConversationLabelList
     */
    public $labelList;

    /**
     * @inheritDoc
     */
    public function validateUpdate()
    {
        parent::validateUpdate();

        $label = $this->getSingleObject();
        if ($label->userID != WCF::getUser()->userID) {
            throw new PermissionDeniedException();
        }
    }

    /**
     * @inheritDoc
     */
    public function validateDelete()
    {
        parent::validateDelete();

        $label = $this->getSingleObject();
        if ($label->userID != WCF::getUser()->userID) {
            throw new PermissionDeniedException();
        }
    }

    /**
     * Validates parameters to add a new label.
     *
     * @throws  PermissionDeniedException
     * @throws  UserInputException
     */
    public function validateAdd()
    {
        if (!WCF::getSession()->getPermission('user.conversation.canUseConversation')) {
            throw new PermissionDeniedException();
        }

        // check if user has already created maximum number of labels
        if (\count(ConversationLabel::getLabelsByUser()) >= WCF::getSession()->getPermission('user.conversation.maxLabels')) {
            throw new PermissionDeniedException();
        }

        $this->readString('labelName', false, 'data');
        $this->readString('cssClassName', false, 'data');
        if (!\in_array($this->parameters['data']['cssClassName'], ConversationLabel::getLabelCssClassNames())) {
            throw new UserInputException('cssClassName');
        }

        // 'none' is a pseudo value
        if ($this->parameters['data']['cssClassName'] == 'none') {
            $this->parameters['data']['cssClassName'] = '';
        }
    }

    /**
     * Adds a new user-specific label.
     *
     * @return  array
     */
    public function add()
    {
        $label = ConversationLabelEditor::create([
            'userID' => WCF::getUser()->userID,
            'label' => $this->parameters['data']['labelName'],
            'cssClassName' => $this->parameters['data']['cssClassName'],
        ]);

        return [
            'actionName' => 'add',
            'cssClassName' => $label->cssClassName,
            'label' => StringUtil::encodeHTML($label->label),
            'labelID' => $label->labelID,
        ];
    }
}
