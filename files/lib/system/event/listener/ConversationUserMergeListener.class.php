<?php
namespace wcf\system\event\listener;

/**
 * Merges user conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.event.listener
 * @category	Community Framework
 */
class ConversationUserMergeListener extends AbstractUserMergeListener {
	/**
	 * @inheritDoc
	 */
	protected $databaseTables = [
		'wcf{WCF_N}_conversation',
		'wcf{WCF_N}_conversation_message',
		[
			'name' => 'wcf{WCF_N}_conversation_label',
			'username' => null
		],
		[
			'name' => 'wcf{WCF_N}_conversation_to_user',
			'userID' => 'participantID',
			'username' => null,
			'ignore' => true
		]
	];
}
