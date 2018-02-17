<?php
namespace wcf\system\event\listener;

/**
 * Merges user conversations.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2018 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\Event\Listener
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
