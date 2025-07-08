<?php

namespace wcf\system\interaction\user;

use wcf\action\ConversationLabelFormAction;
use wcf\data\conversation\label\ConversationLabel;
use wcf\event\interaction\user\ConversationLabelInteractionCollecting;
use wcf\system\event\EventHandler;
use wcf\system\interaction\AbstractInteractionProvider;
use wcf\system\interaction\DeleteInteraction;
use wcf\system\interaction\Divider;
use wcf\system\interaction\FormBuilderDialogInteraction;
use wcf\system\request\LinkHandler;

/**
 * Interaction provider for conversation labels.
 *
 * @author Olaf Braun
 * @copyright 2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
final class ConversationLabelInteractions extends AbstractInteractionProvider
{
    public function __construct()
    {
        $this->addInteractions([
            new FormBuilderDialogInteraction(
                'edit',
                LinkHandler::getInstance()->getControllerLink(ConversationLabelFormAction::class, ['labelID' => '%s']),
                'wcf.global.button.edit',
            ),
            new Divider(),
            new DeleteInteraction('core/conversations/labels/%s'),
        ]);

        EventHandler::getInstance()->fire(
            new ConversationLabelInteractionCollecting($this)
        );
    }

    #[\Override]
    public function getObjectClassName(): string
    {
        return ConversationLabel::class;
    }
}
