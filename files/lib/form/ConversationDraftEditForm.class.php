<?php

namespace wcf\form;

use wcf\command\conversation\MarkConversationAsRead;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\message\ConversationMessageAction;
use wcf\data\conversation\message\ConversationMessageList;
use wcf\system\exception\IllegalLinkException;
use wcf\system\form\builder\data\processor\CustomFormDataProcessor;
use wcf\system\form\builder\IFormDocument;
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
    public $formAction = 'edit';

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
    public function finalizeForm()
    {
        parent::finalizeForm();

        $this->form->getDataHandler()
            ->addProcessor(
                new CustomFormDataProcessor(
                    'messageDataProcessor',
                    function (IFormDocument $document, array $parameters) {
                        $messageData = [
                            'htmlInputProcessor' => $parameters['message_htmlInputProcessor'],
                            'attachmentHandler' => $parameters['message_attachmentHandler'],
                            'data' => [],
                        ];
                        if ($parameters['data']['isDraft']) {
                            $messageData['data']['time'] = \TIME_NOW;
                        }

                        unset($parameters['message_htmlInputProcessor'], $parameters['message_attachmentHandler']);

                        $parameters['messageData'] = $messageData;

                        return $parameters;
                    }
                )
            )
            ->addProcessor(
                new CustomFormDataProcessor('timeProcessor', function (IFormDocument $document, array $parameters) {
                    if (!$parameters['data']['isDraft']) {
                        $parameters['data']['time'] = $parameters['data']['lastPostTime'] = \TIME_NOW;
                    }

                    return $parameters;
                })
            );
    }

    #[\Override]
    public function saved()
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
            (new MarkConversationAsRead($conversation, WCF::getUser()))();
        }

        parent::saved();
    }
}
