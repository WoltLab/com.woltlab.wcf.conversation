<?php

namespace wcf\form;

use wcf\command\conversation\MarkConversationAsRead;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\message\ConversationMessageAction;
use wcf\data\conversation\message\ConversationMessageList;
use wcf\data\DatabaseObjectBuilder;
use wcf\system\exception\IllegalLinkException;
use wcf\system\WCF;

/**
 * Allows the editing of conversation drafts.
 *
 * @author      Olaf Braun, Marcel Werk
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class ConversationDraftEditForm extends ConversationAddForm
{
    /**
     * @inheritDoc
     */
    public $templateName = 'conversationAdd';

    /**
     * @inheritDoc
     */
    public string $formAction = 'edit';

    #[\Override]
    public function readParameters()
    {
        parent::readParameters();

        if (!isset($_REQUEST['id'])) {
            throw new IllegalLinkException();
        }
        $this->formObject = new Conversation(\intval($_REQUEST['id']));
        if ($this->formObject->userID !== WCF::getUser()->userID || $this->formObject->isDraft === 0) {
            throw new IllegalLinkException();
        }
    }

    #[\Override]
    protected function getAttachmentObjectID(): ?int
    {
        return $this->formObject->firstMessageID;
    }

    #[\Override]
    protected function getCommand(DatabaseObjectBuilder $builder): callable
    {
        $builder->setTime(\TIME_NOW);
        if (($builder->properties['isDraft'] ?? 0) === 1) {
            $builder->getMessageBuilder()->setTime(\TIME_NOW);
        } else {
            $builder->setLastPostTime(\TIME_NOW);
        }

        return parent::getCommand($builder);
    }

    #[\Override]
    public function saved(): void
    {
        // Reload conversation object to get updated data.
        $conversation = new Conversation($this->formObject->conversationID);

        // Update timestamp of other messages in this draft.
        if ($conversation->isDraft === 0) {
            $list = new ConversationMessageList();
            $list->getConditionBuilder()->add('conversationID = ?', [$conversation->conversationID]);
            $list->getConditionBuilder()->add('messageID <> ?', [$conversation->getFirstMessage()->messageID]);
            $list->readObjectIDs();

            if (\count($list->getObjectIDs())) {
                $messageAction = new ConversationMessageAction(
                    $list->getObjectIDs(),
                    'update',
                    [
                        'data' => [
                            'time' => \TIME_NOW,
                        ],
                    ]
                );
                $messageAction->executeAction();
            }
        }

        if ($conversation->isDraft === 0) {
            new MarkConversationAsRead($conversation, WCF::getUser())();
        }

        parent::saved();
    }
}
