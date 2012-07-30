<?php
namespace wcf\data\conversation\message;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationEditor;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\bbcode\MessageParser;
use wcf\system\package\PackageDependencyHandler;
use wcf\system\search\SearchIndexManager;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Executes message-related actions.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation.message
 * @category 	Community Framework
 */
class ConversationMessageAction extends AbstractDatabaseObjectAction {
	/**
	 * @see wcf\data\AbstractDatabaseObjectAction::$className
	 */
	protected $className = 'wcf\data\conversation\message\ConversationMessageEditor';
	
	/**
	 * @see wcf\data\AbstractDatabaseObjectAction::create()
	 */
	public function create() {
		// count attachments
		if (isset($this->parameters['attachmentHandler']) && $this->parameters['attachmentHandler'] !== null) {
			$this->parameters['data']['attachments'] = count($this->parameters['attachmentHandler']);
		}
		
		if (LOG_IP_ADDRESS) {
			// add ip address
			if (!isset($this->parameters['data']['ipAddress'])) {
				$this->parameters['data']['ipAddress'] = WCF::getSession()->ipAddress;
			}
		}
		else {
			// do not track ip address
			if (isset($this->parameters['data']['ipAddress'])) {
				unset($this->parameters['data']['ipAddress']);
			}
		}
		
		// create message
		$message = parent::create();
		
		// get thread
		$converation = (isset($this->parameters['converation']) ? $this->parameters['converation'] : new Conversation($message->conversationID));
		$conversationEditor = new ConversationEditor($converation);

		if (empty($this->parameters['isFirstPost'])) {
			// update last message
			$conversationEditor->addMessage($message);
		}
		
		// reset storage
		UserStorageHandler::getInstance()->reset($converation->getParticipantIDs(), 'unreadConversationCount', PackageDependencyHandler::getInstance()->getPackageID('com.woltlab.wcf.conversation'));
		
		// update search index
		SearchIndexManager::getInstance()->add('com.woltlab.wcf.conversation.message', $message->messageID, $message->message, (!empty($this->parameters['isFirstPost']) ? $converation->subject : ''), $message->time, $message->userID, $message->username);
		
		// update attachments
		if (isset($this->parameters['attachmentHandler']) && $this->parameters['attachmentHandler'] !== null) {
			$this->parameters['attachmentHandler']->updateObjectID($message->messageID);
		}
		
		// return new message
		return $message;
	}
	
	/**
	 * @see wcf\data\AbstractDatabaseObjectAction::update()
	 */
	public function update() {
		// count attachments
		if (isset($this->parameters['attachmentHandler']) && $this->parameters['attachmentHandler'] !== null) {
			$this->parameters['data']['attachments'] = count($this->parameters['attachmentHandler']);
		}
		
		parent::update();
		
		// @todo: update search index
	}
}
