<?php

namespace wcf\system\message\quote;

use wcf\data\conversation\message\ConversationMessage;
use wcf\data\IMessage;

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
        $message = new ConversationMessage($objectID);
        if ($message->isNil()) {
            return null;
        }

        if (!$message->canRead()) {
            return null;
        }

        if ($message->hasEmbeddedObjects === 1) {
            $message->loadEmbeddedObjects();
        }

        return $message;
    }
}
