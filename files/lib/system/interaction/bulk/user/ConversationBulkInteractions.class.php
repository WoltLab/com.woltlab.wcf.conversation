<?php

namespace wcf\system\interaction\bulk\user;

use wcf\action\AssignConversationLabelDialogAction;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\UserConversationList;
use wcf\event\interaction\bulk\user\ConversationBulkInteractionCollecting;
use wcf\system\event\EventHandler;
use wcf\system\interaction\bulk\AbstractBulkInteractionProvider;
use wcf\system\interaction\bulk\BulkFormBuilderDialogInteraction;
use wcf\system\interaction\bulk\BulkRpcInteraction;
use wcf\system\interaction\InteractionConfirmationType;
use wcf\system\WCF;

/**
 * Bulk interaction provider for conversations.
 *
 * @author Olaf Braun
 * @copyright 2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
final class ConversationBulkInteractions extends AbstractBulkInteractionProvider
{
    public function __construct()
    {
        $this->addInteractions([
            new BulkRpcInteraction(
                'open',
                'core/conversations/%s/open',
                'wcf.conversation.edit.open',
                isAvailableCallback: static fn(Conversation $conversation) => $conversation->isClosed && $conversation->userID === WCF::getUser()->userID
            ),
            new BulkRpcInteraction(
                'close',
                'core/conversations/%s/close',
                'wcf.conversation.edit.close',
                isAvailableCallback: static fn(Conversation $conversation) => !$conversation->isClosed && $conversation->userID === WCF::getUser()->userID
            ),
            new BulkFormBuilderDialogInteraction(
                'assignLabel',
                AssignConversationLabelDialogAction::class,
                'wcf.conversation.edit.assignLabel',
                static fn() => ConversationLabel::getUserLabels() !== [],
            ),
            new BulkRpcInteraction(
                'restore',
                'core/conversations/%s/restore',
                'wcf.conversation.hideConversation.restore',
                InteractionConfirmationType::Custom,
                'wcf.conversation.hideConversation.restore.confirmationMessage',
                static fn(Conversation $conversation) => (bool)$conversation->hideConversation
            ),
            new BulkRpcInteraction(
                'hide',
                'core/conversations/%s/hide',
                'wcf.conversation.hideConversation.hide',
                InteractionConfirmationType::Custom,
                'wcf.conversation.hideConversation.hide.confirmationMessage',
                static fn(Conversation $conversation) => !$conversation->hideConversation
            ),
            new BulkRpcInteraction(
                'leave',
                'core/conversations/%s/leave',
                'wcf.conversation.hideConversation.leave',
                InteractionConfirmationType::Custom,
                'wcf.conversation.hideConversation.leave.confirmationMessage'
            ),
        ]);

        EventHandler::getInstance()->fire(
            new ConversationBulkInteractionCollecting($this)
        );
    }

    #[\Override]
    public function getObjectListClassName(): string
    {
        return UserConversationList::class;
    }
}
