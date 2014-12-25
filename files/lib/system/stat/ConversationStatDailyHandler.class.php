<?php
namespace wcf\system\stat;

/**
 * Stat handler implementation for conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2014 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.stat
 * @category	Community Framework
 */
class ConversationStatDailyHandler extends AbstractStatDailyHandler {
	/**
	 * @see	\wcf\system\stat\IStatDailyHandler::getData()
	 */
	public function getData($date) {
		return array(
			'counter' => $this->getCounter($date, 'wcf'.WCF_N.'_conversation', 'time'),
			'total' => $this->getTotal($date, 'wcf'.WCF_N.'_conversation', 'time')
		);
	}
}
