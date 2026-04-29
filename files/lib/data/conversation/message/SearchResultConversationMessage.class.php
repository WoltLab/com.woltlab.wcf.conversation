<?php

namespace wcf\data\conversation\message;

use wcf\data\DatabaseObjectDecorator;
use wcf\data\search\ISearchResultObject;
use wcf\data\user\UserProfile;
use wcf\page\ConversationPage;
use wcf\system\request\LinkHandler;
use wcf\system\search\SearchResultTextParser;

/**
 * Represents a conversation message as a search result.
 *
 * @author      Marcel Werk
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @property-read ?string $subject
 * @mixin ConversationMessage
 * @extends DatabaseObjectDecorator<ConversationMessage>
 */
class SearchResultConversationMessage extends DatabaseObjectDecorator implements ISearchResultObject
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = ConversationMessage::class;

    #[\Override]
    public function getFormattedMessage()
    {
        return SearchResultTextParser::getInstance()->parse(
            $this->getDecoratedObject()->getSimplifiedFormattedMessage()
        );
    }

    #[\Override]
    public function getSubject()
    {
        return $this->subject;
    }

    #[\Override]
    public function getLink(string $query = '')
    {
        if ($query !== '') {
            return LinkHandler::getInstance()->getControllerLink(ConversationPage::class, [
                'object' => $this->getConversation(),
                'messageID' => $this->messageID,
                'highlight' => \urlencode($query),
            ], '#message' . $this->messageID);
        }

        return $this->getDecoratedObject()->getLink();
    }

    #[\Override]
    public function getTime()
    {
        return $this->time;
    }

    #[\Override]
    public function getObjectTypeName()
    {
        return 'com.woltlab.wcf.conversation.message';
    }

    #[\Override]
    public function getContainerTitle()
    {
        return '';
    }

    #[\Override]
    public function getContainerLink()
    {
        return '';
    }

    #[\Override]
    public function getUserProfile(): UserProfile
    {
        return $this->getDecoratedObject()->getUserProfile();
    }
}
