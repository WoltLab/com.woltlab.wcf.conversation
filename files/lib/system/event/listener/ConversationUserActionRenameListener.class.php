<?php
namespace wcf\system\event\listener;

/**
 * Updates the stored username during user rename.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.event.listener
 * @category	Community Framework
 */
class ConversationUserActionRenameListener extends AbstractUserActionRenameListener {
	/**
	 * @inheritDoc
	 */
	protected $databaseTables = [
		'wcf{WCF_N}_conversation',
		'wcf{WCF_N}_conversation_message',
		[
			'name' => 'wcf{WCF_N}_conversation',
			'userID' => 'lastPosterID',
			'username' => 'lastPoster'
		]
	];
}
