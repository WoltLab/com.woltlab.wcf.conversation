<?php

namespace wcf\data\conversation\label;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Executes label-related actions.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractDatabaseObjectAction<ConversationLabel, ConversationLabelEditor>
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
}
