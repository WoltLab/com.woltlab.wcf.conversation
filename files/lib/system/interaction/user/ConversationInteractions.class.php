<?php

namespace wcf\system\interaction\user;

use wcf\action\AddConversationParticipantDialogAction;
use wcf\action\AssignConversationLabelDialogAction;
use wcf\action\EditSubjectConversationDialogAction;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\label\ConversationLabel;
use wcf\event\interaction\user\ConversationInteractionCollecting;
use wcf\form\ConversationDraftEditForm;
use wcf\system\event\EventHandler;
use wcf\system\interaction\AbstractInteractionProvider;
use wcf\system\interaction\Divider;
use wcf\system\interaction\EditInteraction;
use wcf\system\interaction\FormBuilderDialogInteraction;
use wcf\system\interaction\InteractionConfirmationType;
use wcf\system\interaction\InteractionEffect;
use wcf\system\interaction\RpcInteraction;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;

/**
 * Interaction provider for conversations.
 *
 * @author Olaf Braun
 * @copyright 2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
final class ConversationInteractions extends AbstractInteractionProvider
{
    public function __construct()
    {
        $this->addInteractions([
            new FormBuilderDialogInteraction(
                'editSubject',
                LinkHandler::getInstance()->getControllerLink(EditSubjectConversationDialogAction::class, ['id' => '%s']),
                'wcf.conversation.edit.subject',
                static fn(Conversation $conversation) => WCF::getUser()->userID === $conversation->userID,
            ),
            new RpcInteraction(
                'open',
                'core/conversations/%s/open',
                'wcf.conversation.edit.open',
                isAvailableCallback: static fn(Conversation $conversation) => $conversation->isClosed === 1 && $conversation->userID === WCF::getUser()->userID
            ),
            new RpcInteraction(
                'close',
                'core/conversations/%s/close',
                'wcf.conversation.edit.close',
                isAvailableCallback: static fn(Conversation $conversation) => $conversation->isClosed === 0 && $conversation->userID === WCF::getUser()->userID
            ),
            new FormBuilderDialogInteraction(
                'assignLabel',
                LinkHandler::getInstance()->getControllerLink(AssignConversationLabelDialogAction::class, ['id' => '%s']),
                'wcf.conversation.edit.assignLabel',
                static fn() => ConversationLabel::getUserLabels() !== [],
            ),
            new Divider(),
            new FormBuilderDialogInteraction(
                'addParticipants',
                LinkHandler::getInstance()->getControllerLink(AddConversationParticipantDialogAction::class, ['id' => '%s']),
                'wcf.conversation.edit.addParticipants',
                static fn(Conversation $conversation) => $conversation->canAddParticipants(),
            ),
            new RpcInteraction(
                'restore',
                'core/conversations/%s/restore',
                'wcf.conversation.hideConversation.restore',
                InteractionConfirmationType::Custom,
                'wcf.conversation.hideConversation.restore.confirmationMessage',
                static function (Conversation $conversation) {
                    return (bool)$conversation->hideConversation;
                },
            ),
            new RpcInteraction(
                'hide',
                'core/conversations/%s/hide',
                'wcf.conversation.hideConversation.hide',
                InteractionConfirmationType::Custom,
                'wcf.conversation.hideConversation.hide.confirmationMessage',
                static function (Conversation $conversation) {
                    return $conversation->hideConversation === null || $conversation->hideConversation === 0;
                },
            ),
            new RpcInteraction(
                'leave',
                'core/conversations/%s/leave',
                'wcf.conversation.hideConversation.leave',
                InteractionConfirmationType::Custom,
                'wcf.conversation.hideConversation.leave.confirmationMessage',
                interactionEffect: InteractionEffect::RemoveItem,
            ),
            new EditInteraction(
                ConversationDraftEditForm::class,
                static function (Conversation $conversation) {
                    return $conversation->isDraft === 1;
                }
            ),
        ]);

        EventHandler::getInstance()->fire(
            new ConversationInteractionCollecting($this)
        );
    }

    #[\Override]
    public function getObjectClassName(): string
    {
        return Conversation::class;
    }
}
