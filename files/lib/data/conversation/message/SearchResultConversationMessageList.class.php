<?php

namespace wcf\data\conversation\message;

/**
 * Represents a list of search results.
 *
 * @author      Marcel Werk
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends ConversationMessageList<SearchResultConversationMessage>
 */
class SearchResultConversationMessageList extends ConversationMessageList
{
    /**
     * @inheritDoc
     */
    public $decoratorClassName = SearchResultConversationMessage::class;

    public function __construct()
    {
        parent::__construct();

        if (!empty($this->sqlSelects)) {
            $this->sqlSelects .= ',';
        }
        $this->sqlSelects .= 'conversation.subject';
        $this->sqlJoins .= "
            LEFT JOIN   wcf1_conversation conversation
            ON          conversation.conversationID = conversation_message.conversationID";
    }
}
