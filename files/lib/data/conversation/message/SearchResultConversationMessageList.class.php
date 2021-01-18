<?php

namespace wcf\data\conversation\message;

/**
 * Represents a list of search results.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package WoltLabSuite\Core\Data\Conversation\Message
 *
 * @method  SearchResultConversationMessage     current()
 * @method  SearchResultConversationMessage[]   getObjects()
 * @method  SearchResultConversationMessage|null    search($objectID)
 * @property    SearchResultConversationMessage[] $objects
 */
class SearchResultConversationMessageList extends SimplifiedViewableConversationMessageList
{
    /**
     * @inheritDoc
     */
    public $decoratorClassName = SearchResultConversationMessage::class;

    /**
     * Creates a new SearchResultConversationMessageList object.
     */
    public function __construct()
    {
        parent::__construct();

        if (!empty($this->sqlSelects)) {
            $this->sqlSelects .= ',';
        }
        $this->sqlSelects .= 'conversation.subject';
        $this->sqlJoins .= " LEFT JOIN wcf" . WCF_N . "_conversation conversation ON (conversation.conversationID = conversation_message.conversationID)";
    }
}
