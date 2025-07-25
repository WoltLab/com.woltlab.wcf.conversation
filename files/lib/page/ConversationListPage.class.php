<?php

namespace wcf\page;

use wcf\data\conversation\UserConversationList;
use wcf\system\listView\user\ConversationListView;
use wcf\system\WCF;

/**
 * Shows a list of conversations.
 *
 * @author  Olaf Braun, Marcel Werk
 * @copyright   2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractListViewPage<ConversationListView>
 */
final class ConversationListPage extends AbstractListViewPage
{
    /**
     * @inheritDoc
     */
    public $loginRequired = true;

    /**
     * @inheritDoc
     */
    public $neededModules = ['MODULE_CONVERSATION'];

    /**
     * @inheritDoc
     */
    public $neededPermissions = ['user.conversation.canUseConversation'];

    public string $filter = '';

    public int $conversationCount = 0;

    public int $draftCount = 0;

    public int $hiddenCount = 0;

    public int $outboxCount = 0;

    #[\Override]
    public function readParameters()
    {
        parent::readParameters();

        if (isset($_REQUEST['filter'])) {
            $this->filter = $_REQUEST['filter'];
        }
        if (!\in_array($this->filter, UserConversationList::$availableFilters)) {
            $this->filter = '';
        }
    }

    #[\Override]
    public function readData()
    {
        parent::readData();

        $this->conversationCount = $this->getConversationCount('');
        $this->draftCount = $this->getConversationCount('draft');
        $this->hiddenCount = $this->getConversationCount('hidden');
        $this->outboxCount = $this->getConversationCount('outbox');
    }

    private function getConversationCount(string $filter): int
    {
        return (new UserConversationList(WCF::getUser()->userID, $filter))->countObjects();
    }

    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        WCF::getTPL()->assign([
            'filter' => $this->filter,
            'conversationCount' => $this->conversationCount,
            'draftCount' => $this->draftCount,
            'hiddenCount' => $this->hiddenCount,
            'outboxCount' => $this->outboxCount,
        ]);
    }

    #[\Override]
    protected function createListView(): ConversationListView
    {
        return new ConversationListView($this->filter);
    }

    #[\Override]
    protected function getBaseUrlParameters(): array
    {
        return [
            'filter' => $this->filter ?: null,
        ];
    }
}
