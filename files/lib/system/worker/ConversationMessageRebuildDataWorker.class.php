<?php
namespace wcf\system\worker;
use wcf\data\conversation\message\ConversationMessageEditor;
use wcf\data\conversation\message\ConversationMessageList;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\message\embedded\object\MessageEmbeddedObjectManager;
use wcf\system\search\SearchIndexManager;
use wcf\system\WCF;

/**
 * Worker implementation for updating conversation messages.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2017 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\Worker
 * 
 * @method	ConversationMessageList		getObjectList()
 */
class ConversationMessageRebuildDataWorker extends AbstractRebuildDataWorker {
	/**
	 * @inheritDoc
	 */
	protected $limit = 500;
	
	/**
	 * @var	HtmlInputProcessor
	 */
	protected $htmlInputProcessor;
	
	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * @inheritDoc
	 */
	public function countObjects() {
		if ($this->count === null) {
			$this->count = 0;
			$sql = "SELECT	MAX(messageID) AS messageID
				FROM	wcf".WCF_N."_conversation_message";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute();
			$row = $statement->fetchArray();
			if ($row !== false) $this->count = $row['messageID'];
		}
	}
	
	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * @inheritDoc
	 */
	protected function initObjectList() {
		$this->objectList = new ConversationMessageList();
		$this->objectList->sqlOrderBy = 'conversation_message.messageID';
		$this->objectList->sqlSelects = 'conversation.subject';
		$this->objectList->sqlJoins = 'LEFT JOIN wcf'.WCF_N.'_conversation conversation ON (conversation.firstMessageID = conversation_message.messageID)';
	}
	
	/**
	 * @inheritDoc
	 */
	public function execute() {
		$this->objectList->getConditionBuilder()->add('conversation_message.messageID BETWEEN ? AND ?', [$this->limit * $this->loopCount + 1, $this->limit * $this->loopCount + $this->limit]);
		
		parent::execute();
		
		if (!$this->loopCount) {
			// reset search index
			SearchIndexManager::getInstance()->reset('com.woltlab.wcf.conversation.message');
		}
		
		// prepare statements
		$attachmentObjectType = ObjectTypeCache::getInstance()->getObjectTypeByName('com.woltlab.wcf.attachment.objectType', 'com.woltlab.wcf.conversation.message');
		$sql = "SELECT		COUNT(*) AS attachments
			FROM		wcf".WCF_N."_attachment
			WHERE		objectTypeID = ?
					AND objectID = ?";
		$attachmentStatement = WCF::getDB()->prepareStatement($sql);
		
		foreach ($this->objectList as $message) {
			SearchIndexManager::getInstance()->set(
				'com.woltlab.wcf.conversation.message',
				$message->messageID,
				$message->message, 
				$message->subject ?: '',
				$message->time,
				$message->userID,
				$message->username
			);
			
			$editor = new ConversationMessageEditor($message);
			$data = [];
			
			// count attachments
			$attachmentStatement->execute([$attachmentObjectType->objectTypeID, $message->messageID]);
			$row = $attachmentStatement->fetchSingleRow();
			$data['attachments'] = $row['attachments'];
			
			// update message
			if (!$message->enableHtml) {
				$this->getHtmlInputProcessor()->process($message->message, 'com.woltlab.wcf.conversation.message', $message->messageID, true);
				$data['message'] = $this->getHtmlInputProcessor()->getHtml();
				$data['enableHtml'] = 1;
			}
			else {
				$this->getHtmlInputProcessor()->processEmbeddedContent($message->message, 'com.woltlab.wcf.conversation.message', $message->messageID);
			}
			
			if (MessageEmbeddedObjectManager::getInstance()->registerObjects($this->getHtmlInputProcessor())) {
				$data['hasEmbeddedObjects'] = 1;
			}
			else {
				$data['hasEmbeddedObjects'] = 0;
			}
			
			$editor->update($data);
		}
	}
	
	/**
	 * @return	HtmlInputProcessor
	 */
	protected function getHtmlInputProcessor() {
		if ($this->htmlInputProcessor === null) {
			$this->htmlInputProcessor = new HtmlInputProcessor();
		}
		
		return $this->htmlInputProcessor;
	}
}
