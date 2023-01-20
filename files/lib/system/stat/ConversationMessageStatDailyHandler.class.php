<?php

namespace wcf\system\stat;

/**
 * Stat handler implementation for conversation messages.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class ConversationMessageStatDailyHandler extends AbstractStatDailyHandler
{
    /**
     * @inheritDoc
     */
    public function getData($date)
    {
        return [
            'counter' => $this->getCounter($date, 'wcf' . WCF_N . '_conversation_message', 'time'),
            'total' => $this->getTotal($date, 'wcf' . WCF_N . '_conversation_message', 'time'),
        ];
    }
}
