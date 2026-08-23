<?php

namespace wcf\system\endpoint\controller\core\conversations;

use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Provides the guard for the module state and the base permission that every
 * conversation endpoint must enforce.
 *
 * @author      Alexander Ebert
 * @copyright   2001-2026 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 */
trait TConversationEndpoint
{
    /**
     * The route table is built once and shared through a global cache, therefore
     * the availability of an endpoint cannot be decided during its registration.
     */
    private function assertConversationsAreEnabled(): void
    {
        if (\MODULE_CONVERSATION === 0) {
            throw new IllegalLinkException();
        }

        if (!WCF::getSession()->getPermission('user.conversation.canUseConversation')) {
            throw new PermissionDeniedException();
        }
    }
}
