<?php

namespace wcf\system\message\quote;

use wcf\data\conversation\ConversationList;
use wcf\data\conversation\message\ConversationMessageList;

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
        // read messages
        $messageList = new ConversationMessageList();
        $messageList->setObjectIDs(\array_keys($data));
        $messageList->readObjects();
        $messages = $messageList->getObjects();

        // read conversations
        $conversationIDs = $validMessageIDs = [];
        foreach ($messages as $message) {
            $conversationIDs[] = $message->conversationID;
            $validMessageIDs[] = $message->messageID;
        }

        $quotedMessages = [];
        if (!empty($conversationIDs)) {
            $conversationList = new ConversationList();
            $conversationList->setObjectIDs($conversationIDs);
            $conversationList->readObjects();
            $conversations = $conversationList->getObjects();

            // create QuotedMessage objects
            foreach ($messages as $conversationMessage) {
                $conversationMessage->setConversation($conversations[$conversationMessage->conversationID]);
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
