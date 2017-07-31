<?php
namespace wcf\system\user\notification\event;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationAction;
use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\message\ConversationMessageAction;
use wcf\data\user\UserProfile;

/**
 * TODO
 */
trait TTestableConversationRelatedUserNotificationEvent {
	/**
	 * Creates a conversation for testing.
	 * 
	 * @param	UserProfile	$conversationAuthor
	 * @param	UserProfile	$participant
	 * @return	Conversation
	 */
	public static function createTestConversation(UserProfile $conversationAuthor, UserProfile $participant) {
		return (new ConversationAction([], 'create', [
			'data' => [
				'subject' => 'Test Conversation Subject',
				'time' => TIME_NOW,
				'userID' => $conversationAuthor->userID,
				'username' => $conversationAuthor->username
			],
			'messageData' => [
				'message' => 'Test Conversation Message'
			],
			'participants' => [$participant->userID]
		]))->executeAction()['returnValues'];
	}
	
	/**
	 * Creates a conversation message for testing.
	 * 
	 * @param	UserProfile	$conversationAuthor
	 * @param	UserProfile	$messageAuthor
	 * @return	ConversationMessage
	 */
	public static function createTestConversationMessage(UserProfile $conversationAuthor, UserProfile $messageAuthor) {
		$conversation = self::createTestConversation($conversationAuthor, $messageAuthor);
		
		return (new ConversationMessageAction([], 'create', [
			'data' => [
				'conversationID' => $conversation->conversationID,
				'message' => 'Test Conversation Message Message',
				'time' => TIME_NOW,
				'userID' => $messageAuthor->userID,
				'username' => $messageAuthor->username
			],
			'conversation' => $conversation
		]))->executeAction()['returnValues'];
	}
}
