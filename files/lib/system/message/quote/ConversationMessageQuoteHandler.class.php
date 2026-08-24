<?php

namespace wcf\system\message\quote;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\message\ConversationMessageList;
use wcf\system\WCF;

/**
 * IMessageQuoteHandler implementation for conversation messages.
 *
 * @author  Alexander Ebert
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class ConversationMessageQuoteHandler extends AbstractMessageQuoteHandler
{
    /**
     * @inheritDoc
     */
    protected function getMessages(array $data)
    {
        if (\MODULE_CONVERSATION === 0) {
            return [];
        }

        if (!WCF::getSession()->getPermission('user.conversation.canUseConversation')) {
            return [];
        }

        // read messages
        $messageList = new ConversationMessageList();
        $messageList->setObjectIDs(\array_keys($data));
        $messageList->readObjects();
        $messages = $messageList->getObjects();

        // read conversations
        $conversationIDs = [];
        foreach ($messages as $message) {
            $conversationIDs[] = $message->conversationID;
        }

        $validMessageIDs = [];
        $quotedMessages = [];
        if (!empty($conversationIDs)) {
            // The conversations must be read for the active user, otherwise the
            // participation is unknown and `canRead()` cannot be evaluated.
            $conversations = Conversation::getUserConversations($conversationIDs, WCF::getUser()->userID);

            // create QuotedMessage objects
            foreach ($messages as $conversationMessage) {
                if (!isset($conversations[$conversationMessage->conversationID])) {
                    continue;
                }

                $conversationMessage->setConversation($conversations[$conversationMessage->conversationID]);
                if (!$conversationMessage->canRead()) {
                    continue;
                }

                $validMessageIDs[] = $conversationMessage->messageID;
                $message = new QuotedMessage($conversationMessage);

                foreach (\array_keys($data[$conversationMessage->messageID]) as $quoteID) {
                    $message->addQuote(
                        $quoteID,
                        MessageQuoteManager::getInstance()->getQuote($quoteID, false),  // single quote or excerpt
                        MessageQuoteManager::getInstance()->getQuote($quoteID, true)    // same as above or full quote
                    );
                }

                $quotedMessages[] = $message;
            }
        }

        // check for orphaned quotes
        if (\count($validMessageIDs) != \count($data)) {
            $orphanedQuoteIDs = [];
            foreach ($data as $messageID => $quoteIDs) {
                if (!\in_array($messageID, $validMessageIDs)) {
                    foreach (\array_keys($quoteIDs) as $quoteID) {
                        $orphanedQuoteIDs[] = $quoteID;
                    }
                }
            }

            MessageQuoteManager::getInstance()->removeOrphanedQuotes($orphanedQuoteIDs);
        }

        return $quotedMessages;
    }
}
