<?php

namespace wcf\page;

use wcf\system\gridView\user\ConversationLabelGridView;

/**
 * @author Olaf Braun
 * @copyright 2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 *
 * @extends AbstractGridViewPage<ConversationLabelGridView>
 */
final class ConversationLabelListPage extends AbstractGridViewPage
{
    /**
     * @inheritDoc
     */
    public $loginRequired = true;

    #[\Override]
    protected function createGridView(): ConversationLabelGridView
    {
        return new ConversationLabelGridView();
    }
}
