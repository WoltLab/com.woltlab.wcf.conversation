<?php

namespace wcf\system\interaction\bulk\user;

use wcf\action\AssignConversationLabelDialogAction;
use wcf\data\conversation\ConversationList;
use wcf\data\conversation\label\ConversationLabel;
use wcf\event\interaction\bulk\user\ConversationBulkInteractionCollecting;
use wcf\system\event\EventHandler;
use wcf\system\interaction\bulk\AbstractBulkInteractionProvider;
use wcf\system\interaction\bulk\BulkFormBuilderDialogInteraction;

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
        $labelList = ConversationLabel::getLabelsByUser();

        $this->addInteractions([
            new BulkFormBuilderDialogInteraction(
                'assignLabel',
                AssignConversationLabelDialogAction::class,
                'wcf.conversation.edit.assignLabel',
                static fn () => $labelList->count() > 0,
            ),
        ]);

        EventHandler::getInstance()->fire(
            new ConversationBulkInteractionCollecting($this)
        );
    }

    #[\Override]
    public function getObjectListClassName(): string
    {
        return ConversationList::class;
    }
}
