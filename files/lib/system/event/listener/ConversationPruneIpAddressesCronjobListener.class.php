<?php

namespace wcf\system\event\listener;

use wcf\system\cronjob\PruneIpAddressesCronjob;

/**
 * Prunes the stored ip addresses.
 *
 * @author  Alexander Ebert
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package WoltLabSuite\Core\System\Event\Listener
 * @since       5.2
 */
class ConversationPruneIpAddressesCronjobListener implements IParameterizedEventListener
{
    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        /** @var PruneIpAddressesCronjob $eventObj */
        $eventObj->columns['wcf' . WCF_N . '_conversation_message']['ipAddress'] = 'time';
    }
}
