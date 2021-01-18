<?php

namespace wcf\system\log\modification;

use wcf\data\conversation\Conversation;
use wcf\data\user\User;
use wcf\data\user\UserList;

/**
 * Handles conversation modification logs.
 *
 * @author  Alexander Ebert
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package WoltLabSuite\Core\System\Log\Modification
 */
class ConversationModificationLogHandler extends VoidExtendedModificationLogHandler
{
    /**
     * @inheritDoc
     */
    protected $objectTypeName = 'com.woltlab.wcf.conversation.conversation';

    /**
     * Adds a log entry for newly added conversation participants.
     *
     * @param   Conversation    $conversation
     * @param   integer[]   $participantIDs
     */
    public function addParticipants(Conversation $conversation, array $participantIDs)
    {
        $participants = [];
        $userList = new UserList();
        $userList->setObjectIDs($participantIDs);
        $userList->readObjects();
        foreach ($userList as $user) {
            $participants[] = [
                'userID' => $user->userID,
                'username' => $user->username,
            ];
        }

        $this->add($conversation, 'addParticipants', [
            'participants' => $participants,
        ]);
    }

    /**
     * Adds a log entry for conversation close.
     *
     * @param   Conversation    $conversation
     */
    public function close(Conversation $conversation)
    {
        $this->add($conversation, 'close');
    }

    /**
     * Adds a log entry for conversation open.
     *
     * @param   Conversation    $conversation
     */
    public function open(Conversation $conversation)
    {
        $this->add($conversation, 'open');
    }

    /**
     * Adds a log entry for conversation leave.
     *
     * @param   Conversation    $conversation
     */
    public function leave(Conversation $conversation)
    {
        $this->add($conversation, 'leave');
    }

    /**
     * Adds a log entry for a removed participant.
     *
     * @param   Conversation    $conversation
     * @param   integer     $userID
     */
    public function removeParticipant(Conversation $conversation, $userID)
    {
        $user = new User($userID);
        $this->add($conversation, 'removeParticipant', [
            'userID' => $userID,
            'username' => $user->username,
        ]);
    }

    /**
     * Adds a conversation modification log entry.
     *
     * @param   Conversation    $conversation
     * @param   string      $action
     * @param   array       $additionalData
     */
    public function add(Conversation $conversation, $action, array $additionalData = [])
    {
        $this->createLog($action, $conversation->conversationID, null, $additionalData);
    }

    /**
     * Removes the conversation log entries of the conversations with the given
     * ids.
     *
     * @param   integer[]   $objectIDs
     * @deprecated  3.0, use deleteLogs()
     */
    public function remove(array $objectIDs)
    {
        $this->deleteLogs($objectIDs);
    }
}
