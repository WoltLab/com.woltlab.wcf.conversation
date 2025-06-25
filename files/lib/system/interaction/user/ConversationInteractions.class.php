<?php

namespace wcf\system\interaction\user;

use wcf\action\AssignConversationLabelDialogAction;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\label\ConversationLabel;
use wcf\event\interaction\user\ConversationInteractionCollecting;
use wcf\system\event\EventHandler;
use wcf\system\interaction\AbstractInteractionProvider;
use wcf\system\interaction\Divider;
use wcf\system\interaction\FormBuilderDialogInteraction;
use wcf\system\request\LinkHandler;

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
            // TODO edit subject `wcf.conversation.edit.subject`
            // TODO close `wcf.conversation.edit.close`
            // TODO open `wcf.conversation.edit.open`
            new FormBuilderDialogInteraction(
                'assignLabel',
                LinkHandler::getInstance()->getControllerLink(AssignConversationLabelDialogAction::class, ['id' => '%s']),
                'wcf.conversation.edit.assignLabel',
                static fn () => $labelList->count() > 0,
            ),
            new Divider(),
            // TODO add a participant `wcf.conversation.edit.addParticipants`
            // TODO leave `wcf.conversation.edit.leave`
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
