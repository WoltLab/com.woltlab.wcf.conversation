<?php

namespace wcf\system\interaction\user;

use wcf\action\AddParticipantConversationDialogAction;
use wcf\action\AssignConversationLabelDialogAction;
use wcf\action\EditSubjectConversationDialogAction;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\ViewableConversation;
use wcf\event\interaction\user\ConversationInteractionCollecting;
use wcf\system\cache\runtime\UserConversationRuntimeCache;
use wcf\system\event\EventHandler;
use wcf\system\interaction\AbstractInteractionProvider;
use wcf\system\interaction\Divider;
use wcf\system\interaction\FormBuilderDialogInteraction;
use wcf\system\interaction\InteractionConfirmationType;
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
        $labelList = ConversationLabel::getLabelsByUser();

        $this->addInteractions([
            new FormBuilderDialogInteraction(
                'editSubject',
                LinkHandler::getInstance()->getControllerLink(EditSubjectConversationDialogAction::class, ['id' => '%s']),
                'wcf.conversation.edit.subject',
                static fn (ViewableConversation|Conversation $conversation) => WCF::getUser()->userID === $conversation->userID,
            ),
            new RpcInteraction(
                'open',
                'core/conversations/%s/open',
                'wcf.conversation.edit.open',
                isAvailableCallback: static fn (ViewableConversation|Conversation $conversation) => $conversation->isClosed && $conversation->userID === WCF::getUser()->userID
            ),
            new RpcInteraction(
                'close',
                'core/conversations/%s/close',
                'wcf.conversation.edit.close',
                isAvailableCallback: static fn (ViewableConversation|Conversation $conversation) => !$conversation->isClosed && $conversation->userID === WCF::getUser()->userID
            ),
            new FormBuilderDialogInteraction(
                'assignLabel',
                LinkHandler::getInstance()->getControllerLink(AssignConversationLabelDialogAction::class, ['id' => '%s']),
                'wcf.conversation.edit.assignLabel',
                static fn () => $labelList->count() > 0,
            ),
            new Divider(),
            new FormBuilderDialogInteraction(
                'addParticipants',
                LinkHandler::getInstance()->getControllerLink(AddParticipantConversationDialogAction::class, ['id' => '%s']),
                'wcf.conversation.edit.addParticipants',
                static fn (ViewableConversation|Conversation $conversation) => $conversation->canAddParticipants(),
            ),
            new RpcInteraction(
                'restore',
                'core/conversations/%s/restore',
                'wcf.conversation.hideConversation.restore',
                InteractionConfirmationType::Custom,
                isAvailableCallback: static function (ViewableConversation|Conversation $conversation) {
                    if (!($conversation instanceof ViewableConversation)) {
                        $conversation = UserConversationRuntimeCache::getInstance()->getObject($conversation->conversationID);
                    }

                    return (bool)$conversation->hideConversation;
                },
                invalidatesAllItems: true
            ),
            new RpcInteraction(
                'leave',
                'core/conversations/%s/leave',
                'wcf.conversation.hideConversation.leave',
                InteractionConfirmationType::Custom,
                'wcf.conversation.hideConversation.leave.description',
                static function (ViewableConversation|Conversation $conversation) {
                    if (!($conversation instanceof ViewableConversation)) {
                        $conversation = UserConversationRuntimeCache::getInstance()->getObject($conversation->conversationID);
                    }

                    return !$conversation->hideConversation;
                },
                true
            ),
            new RpcInteraction(
                'leave-permanently',
                'core/conversations/%s/leave-permanently',
                'wcf.conversation.hideConversation.leavePermanently',
                InteractionConfirmationType::Custom,
                'wcf.conversation.hideConversation.leavePermanently.description',
                invalidatesAllItems: true
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
