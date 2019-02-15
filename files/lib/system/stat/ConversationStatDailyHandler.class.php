<?php
namespace wcf\system\stat;

/**
 * Stat handler implementation for conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2019 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\Stat
 */
class ConversationStatDailyHandler extends AbstractStatDailyHandler {
	/**
	 * @inheritDoc
	 */
	public function getData($date) {
		return [
			'counter' => $this->getCounter($date, 'wcf'.WCF_N.'_conversation', 'time'),
			'total' => $this->getTotal($date, 'wcf'.WCF_N.'_conversation', 'time')
		];
	}
}
