<?php

namespace wcf\system\conversation;

use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\NamedUserException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\flood\FloodControl;
use wcf\system\SingletonFactory;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;

/**
 * Handles the number of conversations and unread conversations of the active user.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class ConversationHandler extends SingletonFactory
{
    /**
     * number of unread conversations
     * @var int[]
     */
    protected $unreadConversationCount = [];

    /**
     * number of conversations
     * @var int[]
     */
    protected $conversationCount = [];

    /**
     * Returns the number of unread conversations for given user.
     *
     * @param int $userID
     * @param bool $skipCache
     * @return  int
     */
    public function getUnreadConversationCount($userID = null, $skipCache = false)
    {
        if ($userID === null) {
            $userID = WCF::getUser()->userID;
        }

        if (!isset($this->unreadConversationCount[$userID]) || $skipCache) {
            $this->unreadConversationCount[$userID] = 0;

            // load storage data
            UserStorageHandler::getInstance()->loadStorage([$userID]);

            // get ids
            $data = UserStorageHandler::getInstance()->getStorage([$userID], 'unreadConversationCount');

            // cache does not exist or is outdated
            if ($data[$userID] === null || $skipCache) {
                $conditionBuilder = new PreparedStatementConditionBuilder();
                $conditionBuilder->add('conversation.conversationID = conversation_to_user.conversationID');
                $conditionBuilder->add('conversation_to_user.participantID = ?', [$userID]);
                $conditionBuilder->add('conversation_to_user.hideConversation = 0');
                $conditionBuilder->add('conversation_to_user.lastVisitTime < conversation.lastPostTime');
                $conditionBuilder->add('conversation_to_user.leftAt = 0');

                $sql = "SELECT  COUNT(*) AS count
                        FROM    wcf" . WCF_N . "_conversation_to_user conversation_to_user,
                                wcf" . WCF_N . "_conversation conversation
                        " . $conditionBuilder;
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute($conditionBuilder->getParameters());
                $row = $statement->fetchArray();
                $this->unreadConversationCount[$userID] = $row['count'];

                // update storage data
                UserStorageHandler::getInstance()->update(
                    $userID,
                    'unreadConversationCount',
                    \serialize($this->unreadConversationCount[$userID])
                );
            } else {
                $this->unreadConversationCount[$userID] = \unserialize($data[$userID]);
            }
        }

        return $this->unreadConversationCount[$userID];
    }

    /**
     * Returns the number of conversations for given user.
     *
     * @param int $userID
     * @return  int
     */
    public function getConversationCount($userID = null)
    {
        if ($userID === null) {
            $userID = WCF::getUser()->userID;
        }

        if (!isset($this->conversationCount[$userID])) {
            $this->conversationCount[$userID] = 0;

            // load storage data
            UserStorageHandler::getInstance()->loadStorage([$userID]);

            // get ids
            $data = UserStorageHandler::getInstance()->getStorage([$userID], 'conversationCount');

            // cache does not exist or is outdated
            if ($data[$userID] === null) {
                $conditionBuilder1 = new PreparedStatementConditionBuilder();
                $conditionBuilder1->add('conversation_to_user.participantID = ?', [$userID]);
                $conditionBuilder1->add('conversation_to_user.hideConversation IN (0,1)');
                $conditionBuilder2 = new PreparedStatementConditionBuilder();
                $conditionBuilder2->add('conversation.userID = ?', [$userID]);
                $conditionBuilder2->add('conversation.isDraft = 1');

                $sql = "SELECT (
                            SELECT  COUNT(*)
                            FROM    wcf" . WCF_N . "_conversation_to_user conversation_to_user
                            " . $conditionBuilder1 . "
                        ) + (
                            SELECT  COUNT(*)
                            FROM    wcf" . WCF_N . "_conversation conversation
                            " . $conditionBuilder2 . "
                        ) AS count";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute(\array_merge(
                    $conditionBuilder1->getParameters(),
                    $conditionBuilder2->getParameters()
                ));
                $row = $statement->fetchArray();
                $this->conversationCount[$userID] = $row['count'];

                // update storage data
                UserStorageHandler::getInstance()->update(
                    $userID,
                    'conversationCount',
                    \serialize($this->conversationCount[$userID])
                );
            } else {
                $this->conversationCount[$userID] = \unserialize($data[$userID]);
            }
        }

        return $this->conversationCount[$userID];
    }

    /**
     * Enforces the flood control.
     */
    public function enforceFloodControl(
        bool $isReply = false
    ) {
        if (!$isReply) {
            // 1. Check for the maximum conversations per 24 hours.
            $limit = WCF::getSession()->getPermission('user.conversation.maxStartedConversationsPer24Hours');
            if ($limit == 0) {
                // `0` is not a valid value, but the interface logic does not permit and exclusion
                // while also allowing the special value `-1`. Therefore, `0` behaves like the
                // 'canStartConversation' permission added in WoltLab Suite 5.2.
                throw new PermissionDeniedException();
            }

            if ($limit != -1) {
                $count = FloodControl::getInstance()->countContent('com.woltlab.wcf.conversation', new \DateInterval('P1D'));
                if ($count['count'] >= $limit) {
                    throw new NamedUserException(WCF::getLanguage()->getDynamicVariable(
                        'wcf.conversation.error.floodControl',
                        [
                            'limit' => $count['count'],
                            'notBefore' => $count['earliestTime'] + 86400,
                        ]
                    ));
                }
            }
        }

        // 2. Check the time between conversation messages.
        $floodControlTime = WCF::getSession()->getPermission('user.conversation.floodControlTime');
        $lastTime = FloodControl::getInstance()->getLastTime('com.woltlab.wcf.conversation.message');
        if ($lastTime !== null && $lastTime > TIME_NOW - $floodControlTime) {
            throw new NamedUserException(WCF::getLanguage()->getDynamicVariable(
                'wcf.conversation.message.error.floodControl',
                [
                    'lastMessageTime' => $lastTime,
                    'waitTime' => $lastTime + $floodControlTime - TIME_NOW,
                ]
            ));
        }
    }
}
