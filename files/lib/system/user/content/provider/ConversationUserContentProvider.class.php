<?php

namespace wcf\system\user\content\provider;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationList;

/**
 * User content provider for conversations.
 *
 * @author  Joshua Ruesweg
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since   5.2
 *
 * @extends AbstractDatabaseUserContentProvider<ConversationList>
 */
class ConversationUserContentProvider extends AbstractDatabaseUserContentProvider
{
    /**
     * @inheritdoc
     */
    public static function getDatabaseObjectClass()
    {
        return Conversation::class;
    }
}
