<?php

namespace wcf\system\gridView\user;

use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\label\ConversationLabelList;
use wcf\data\DatabaseObject;
use wcf\event\gridView\user\ConversationLabelGridViewInitialized;
use wcf\system\gridView\AbstractGridView;
use wcf\system\gridView\filter\ObjectIdFilter;
use wcf\system\gridView\filter\TextFilter;
use wcf\system\gridView\GridViewColumn;
use wcf\system\gridView\GridViewRowLink;
use wcf\system\gridView\renderer\DefaultColumnRenderer;
use wcf\system\gridView\renderer\ObjectIdColumnRenderer;
use wcf\system\interaction\user\ConversationLabelInteractions;
use wcf\system\WCF;

/**
 * Grid view for the list of conversation labels.
 *
 * @author Olaf Braun
 * @copyright 2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 *
 * @extends AbstractGridView<ConversationLabel, ConversationLabelList>
 */
final class ConversationLabelGridView extends AbstractGridView
{
    public function __construct()
    {
        $this->addColumns([
            GridViewColumn::for('labelID')
                ->label('wcf.global.objectID')
                ->renderer(new ObjectIdColumnRenderer())
                ->sortable()
                ->filter(new ObjectIdFilter()),
            GridViewColumn::for('label')
                ->label('wcf.global.title')
                ->titleColumn()
                ->sortable()
                ->filter(new TextFilter())
                ->renderer(
                    new
                    /** @template-extends DefaultColumnRenderer<ConversationLabel> */
                    class extends DefaultColumnRenderer {
                        #[\Override]
                        public function render(mixed $value, DatabaseObject $row): string
                        {
                            /** @var ConversationLabel $row */
                            return $row->render();
                        }
                    }
                ),
        ]);

        $interactions = new ConversationLabelInteractions();
        $this->setInteractionProvider($interactions);

        $this->setSortField("labelID");
        $this->setSortOrder("ASC");
        $this->addRowLink(new GridViewRowLink(cssClass: 'editConversationLabel'));
    }

    #[\Override]
    public function isAccessible(): bool
    {
        return WCF::getUser()->userID > 0
            && WCF::getSession()->getPermission('user.conversation.canUseConversation');
    }

    #[\Override]
    protected function createObjectList(): ConversationLabelList
    {
        $list = new ConversationLabelList();
        $list->getConditionBuilder()->add('conversation_label.userID = ?', [WCF::getUser()->userID]);

        return $list;
    }

    #[\Override]
    protected function getInitializedEvent(): ConversationLabelGridViewInitialized
    {
        return new ConversationLabelGridViewInitialized($this);
    }
}
