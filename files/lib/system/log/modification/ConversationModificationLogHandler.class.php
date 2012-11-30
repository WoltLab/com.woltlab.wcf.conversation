<?php
namespace wcf\system\log\modification;
use wcf\data\conversation\Conversation;
use wcf\system\database\util\PreparedStatementConditionBuilder;

/**
 * Handles conversation modification logs.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	system.log.modification
 * @category	Community Framework
 */
class ConversationModificationLogHandler extends ModificationLogHandler {
	/**
	 * Adds a log entry for newly added conversation participants.
	 * 
	 * @param	wcf\data\conversation\Conversation	$conversation
	 * @param	array<integer>				$participantIDs
	 */
	public function addParticipants(Conversation $conversation, array $participantIDs) {
		$this->add($conversation, 'addParticipants', array(
			'participantIDs' => $participantIDs
		));
	}
	
	/**
	 * Adds a log entry for conversation close.
	 * 
	 * @param	wcf\data\conversation\Conversation	$conversation
	 */
	public function close(Conversation $conversation) {
		$this->add($conversation, 'close');
	}
	
	/**
	 * Adds a log entry for conversation open.
	 *
	 * @param	wcf\data\conversation\Conversation	$conversation
	 */
	public function open(Conversation $conversation) {
		$this->add($conversation, 'open');
	}
	
	/**
	 * Adds a conversation modification log entry.
	 * 
	 * @param	wcf\data\conversation\Conversation	$conversation
	 * @param	string					$action
	 * @param	array					$additionalData
	 */
	public function add(Conversation $conversation, $action, array $additionalData = array()) {
		parent::_add('com.woltlab.wcf.conversation.conversation', $conversation->conversationID, $action, $additionalData);
	}
}