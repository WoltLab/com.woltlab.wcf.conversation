<?php

namespace wcf\form;

use wcf\data\conversation\Conversation;
use wcf\system\exception\IllegalLinkException;
use wcf\system\WCF;

/**
 * Allows the editing of conversation drafts.
 *
 * @author      Olaf Braun, Marcel Werk
 * @copyright   2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class ConversationDraftEditForm extends ConversationAddForm
{
    /**
     * @inheritDoc
     */
    public $templateName = 'conversationAdd';

    #[\Override]
    public function readParameters()
    {
        parent::readParameters();

        if (!isset($_REQUEST['id'])) {
            throw new IllegalLinkException();
        }
        $this->formObject = new Conversation(\intval($_REQUEST['id']));
        if ($this->formObject->userID != WCF::getUser()->userID || !$this->formObject->isDraft) {
            throw new IllegalLinkException();
        }
    }
}
