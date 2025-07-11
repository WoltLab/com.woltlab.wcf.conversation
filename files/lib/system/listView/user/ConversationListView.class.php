<?php

namespace wcf\system\listView\user;

use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\UserConversationList;
use wcf\data\conversation\ViewableConversation;
use wcf\data\DatabaseObjectList;
use wcf\event\listView\user\ConversationListViewInitialized;
use wcf\system\form\builder\field\AbstractFormField;
use wcf\system\form\builder\field\ConversationLabelFormField;
use wcf\system\interaction\bulk\user\ConversationBulkInteractions;
use wcf\system\interaction\user\ConversationInteractions;
use wcf\system\listView\AbstractListView;
use wcf\system\listView\filter\AbstractFilter;
use wcf\system\listView\filter\TextFilter;
use wcf\system\listView\filter\UserFilter;
use wcf\system\listView\ListViewSortField;
use wcf\system\WCF;

/**
 * @author Olaf Braun
 * @copyright 2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 *
 * @extends AbstractListView<ViewableConversation, UserConversationList>
 */
final class ConversationListView extends AbstractListView
{
    public readonly string $filter;

    public function __construct(string $filter = '')
    {
        if ($filter === '' || \in_array($filter, UserConversationList::$availableFilters)) {
            $this->filter = $filter;
        } else {
            $this->filter = '';
        }

        $this->addAvailableSortFields([
            new ListViewSortField('time', 'wcf.global.date'),
            new ListViewSortField('subject', 'wcf.global.title'),
            new ListViewSortField('lastPostTime', 'wcf.conversation.lastPostTime'),
            new ListViewSortField('username', 'wcf.user.username'),
            new ListViewSortField('replies', 'wcf.conversation.replies'),
            new ListViewSortField('participants', 'wcf.conversation.participants'),
        ]);

        $this->addAvailableFilter(new TextFilter('subject', 'wcf.global.title'));
        // Participants in draft conversations are not yet stored in the `wcf1_conversation_to_user` table,
        // they are serialized in `draftData`.
        if ($filter !== 'draft') {
            $this->addAvailableFilter($this->getParticipantFilter());
        }
        if (ConversationLabel::getUserLabels() !== []) {
            $this->addAvailableFilter($this->getLabelFilter());
        }

        $this->setInteractionProvider(new ConversationInteractions());
        $this->setBulkInteractionProvider(new ConversationBulkInteractions());

        $this->setItemsPerPage(WCF::getUser()->conversationsPerPage ?: \CONVERSATIONS_PER_PAGE);
        $this->setSortField(\CONVERSATION_LIST_DEFAULT_SORT_FIELD);
        $this->setSortOrder(\CONVERSATION_LIST_DEFAULT_SORT_ORDER);
        $this->setCssClassName("tabularList");
    }

    #[\Override]
    protected function createObjectList(): UserConversationList
    {
        return new UserConversationList(WCF::getUser()->userID, $this->filter);
    }

    #[\Override]
    public function renderItems(): string
    {
        return WCF::getTPL()->render('wcf', 'conversationListItems', ['view' => $this]);
    }

    #[\Override]
    protected function getInitializedEvent(): ConversationListViewInitialized
    {
        return new ConversationListViewInitialized($this);
    }

    #[\Override]
    public function getParameters(): array
    {
        return ['filter' => $this->filter];
    }

    private function getParticipantFilter(): AbstractFilter
    {
        return new class(id: 'participants', languageItem: 'wcf.conversation.participants') extends UserFilter {
            #[\Override]
            public function applyFilter(DatabaseObjectList $list, string $value): void
            {
                // The condition is split into two branches in order to account for invisible participants.
                // Invisible participants are only visible to the conversation starter and remain invisible
                // until they write their first message.
                //
                // We need to protect these users from being exposed as participants by including them for
                // any conversation that the current user has started. For all other conversations, users
                // flagged with `isInvisible = 0` must be excluded.
                //
                // See https://github.com/WoltLab/com.woltlab.wcf.conversation/issues/131
                $list->getConditionBuilder()->add(
                    "(
                        (
                            conversation.userID = ?
                            AND conversation.conversationID IN (
                                SELECT      conversationID
                                FROM        wcf1_conversation_to_user
                                WHERE       participantID = ?
                            )
                        )
                        OR
                        (
                            conversation.userID <> ?
                            AND conversation.conversationID IN (
                                SELECT      conversationID
                                FROM        wcf1_conversation_to_user
                                WHERE       participantID = ?
                                        AND isInvisible = ?
                            )
                        )
                    )",
                    [
                        // Parameters for the first condition.
                        WCF::getUser()->userID,
                        $value,

                        // Parameters for the second condition.
                        WCF::getUser()->userID,
                        $value,
                        0,
                    ]
                );
            }
        };
    }

    private function getLabelFilter(): AbstractFilter
    {
        return new class extends AbstractFilter {
            public function __construct()
            {
                parent::__construct('label', 'wcf.label.label');
            }

            public function getFormField(): AbstractFormField
            {
                return ConversationLabelFormField::create('label')
                    ->label($this->languageItem)
                    ->labels(ConversationLabel::getUserLabels());
            }

            public function applyFilter(DatabaseObjectList $list, string $value): void
            {
                $list->getConditionBuilder()->add(
                    "{$list->getDatabaseTableAlias()}.{$list->getDatabaseTableIndexName()} IN (
                        SELECT  conversationID
                        FROM    wcf1_conversation_label_to_object
                        WHERE   labelID = ?
                    )",
                    [$value]
                );
            }

            #[\Override]
            public function renderValue(string $value): string
            {
                return ConversationLabel::getUserLabels()[$value]->label;
            }
        };
    }
}
