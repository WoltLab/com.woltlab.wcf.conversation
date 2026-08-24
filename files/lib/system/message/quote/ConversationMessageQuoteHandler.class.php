<?php

namespace wcf\system\message\quote;

use wcf\data\conversation\message\ConversationMessage;
use wcf\data\IMessage;
use wcf\system\WCF;

/**
 * IMessageQuoteHandler implementation for conversation messages.
 *
 * @author      Alexander Ebert
 * @copyright   2001-2025 WoltLab GmbH
 * @license     WoltLab License <http://www.woltlab.com/license-agreement.html>
 */
final class ConversationMessageQuoteHandler extends AbstractMessageQuoteHandler
{
    #[\Override]
    public function getMessage(int $objectID): ?IMessage
    {
        if (\MODULE_CONVERSATION === 0) {
            return null;
        }

        if (!WCF::getSession()->getPermission('user.conversation.canUseConversation')) {
            return null;
        }

        $message = new ConversationMessage($objectID);
        if (!$message->messageID) {
            return null;
        }

        if (!$message->canRead()) {
            return null;
        }

        if ($message->hasEmbeddedObjects) {
            $message->getCollection()->loadEmbeddedObjects();
        }

        return $message;
    }
}
