<?php

namespace wcf\system\page\handler;

use wcf\data\conversation\Conversation;
use wcf\data\page\Page;
use wcf\data\user\online\UserOnline;
use wcf\system\cache\runtime\UserConversationRuntimeCache;
use wcf\system\WCF;

/**
 * Implementation of the online location-related page handler methods for conversations.
 *
 * @author  Matthias Schmidt
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since   3.0
 */
trait TConversationOnlineLocationPageHandler
{
    use TOnlineLocationPageHandler;

    /**
     * Returns the textual description if a user is currently online viewing this page.
     *
     * @param Page $page visited page
     * @param UserOnline $user user online object with request data
     * @return  string
     * @see IOnlineLocationPageHandler::getOnlineLocation()
     *
     */
    public function getOnlineLocation(Page $page, UserOnline $user)
    {
        if ($user->pageObjectID === null) {
            return '';
        }

        $conversation = UserConversationRuntimeCache::getInstance()->getObject($user->pageObjectID);
        if ($conversation === null || !$conversation->canRead()) {
            return '';
        }

        // Guest and spiders can never be reading a conversation.
        if (!$user->userID) {
            return '';
        }

        // `pageObjectID` is taken from the request of the visited page and is
        // never validated against the permissions of that user, therefore the
        // participation has to be verified here. Drafts have no rows in
        // `conversation_to_user` at all, leaving the author as the only reader.
        $userConversation = Conversation::getUserConversation($conversation->conversationID, $user->userID);
        $isParticipant = $userConversation !== null && $userConversation->participantID !== null;
        if (!$isParticipant && $conversation->userID != $user->userID) {
            return '';
        }

        if ($conversation->userID != WCF::getUser()->userID && $user->userID != WCF::getUser()->userID) {
            // Make sure that requests from invisible participants are not listed
            // if the active user is not the author of the conversation.
            if ($isParticipant && $userConversation->isInvisible) {
                return '';
            }
        }

        return WCF::getLanguage()->getDynamicVariable(
            'wcf.page.onlineLocation.' . $page->identifier,
            ['conversation' => $conversation]
        );
    }

    /**
     * Prepares fetching all necessary data for the textual description if a user is currently online
     * viewing this page.
     *
     * @param Page $page visited page
     * @param UserOnline $user user online object with request data
     * @see IOnlineLocationPageHandler::prepareOnlineLocation()
     */
    public function prepareOnlineLocation(
        Page $page,
        UserOnline $user
    ) {
        if ($user->pageObjectID !== null) {
            UserConversationRuntimeCache::getInstance()->cacheObjectID($user->pageObjectID);
        }
    }
}
