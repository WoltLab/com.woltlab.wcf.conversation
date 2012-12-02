<?php
namespace wcf\data\modification\log;
use wcf\data\DatabaseObjectDecorator;
use wcf\system\WCF;

/**
 * Provides a viewable conversation modification log.
 *
 * @author	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.modification.log
 * @category	Community Framework
 */
class ViewableConversationModificationLog extends DatabaseObjectDecorator {
	/**
	 * @see	wcf\data\DatabaseObjectDecorator::$baseClass
	 */
	protected static $baseClass = 'wcf\data\modification\log\ModificationLog';
	
	/**
	 * Returns readable representation of current log entry.
	 */
	public function __toString() {
		return WCF::getLanguage()->getDynamicVariable('wcf.conversation.log.conversation.'.$this->action, array('additionalData' => $this->additionalData));
	}
}