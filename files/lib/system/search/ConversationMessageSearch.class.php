<?php

namespace wcf\system\search;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\message\SearchResultConversationMessage;
use wcf\data\conversation\message\SearchResultConversationMessageList;
use wcf\data\search\ISearchResultObject;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * An implementation of ISearchProvider for searching in conversations.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package WoltLabSuite\Core\System\Search
 */
class ConversationMessageSearch extends AbstractSearchProvider
{
    /**
     * @var int
     */
    public $conversationID = 0;

    /**
     * searched conversation
     * @var Conversation
     */
    public $conversation;

    /**
     * @var SearchResultConversationMessage[]
     */
    public $messageCache = [];

    /**
     * @inheritDoc
     */
    public function cacheObjects(array $objectIDs, ?array $additionalData = null): void
    {
        $messageList = new SearchResultConversationMessageList();
        $messageList->setObjectIDs($objectIDs);
        $messageList->readObjects();
        foreach ($messageList->getObjects() as $message) {
            $this->messageCache[$message->messageID] = $message;
        }
    }

    /**
     * @inheritDoc
     */
    public function getAdditionalData(): ?array
    {
        return [
            'conversationID' => $this->conversationID,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getObject(int $objectID): ?ISearchResultObject
    {
        return $this->messageCache[$objectID] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getJoins(): string
    {
        return "    JOIN        wcf" . WCF_N . "_conversation_to_user conversation_to_user
                    ON          conversation_to_user.participantID = " . WCF::getUser()->userID . "
                            AND conversation_to_user.conversationID = " . $this->getTableName() . ".conversationID
                    LEFT JOIN   wcf" . WCF_N . "_conversation conversation
                    ON          conversation.conversationID = " . $this->getTableName() . ".conversationID";
    }

    /**
     * @inheritDoc
     */
    public function getTableName(): string
    {
        return 'wcf' . WCF_N . '_conversation_message';
    }

    /**
     * @inheritDoc
     */
    public function getIDFieldName(): string
    {
        return $this->getTableName() . '.messageID';
    }

    /**
     * @inheritDoc
     */
    public function getSubjectFieldName(): string
    {
        return 'conversation.subject';
    }

    /**
     * @inheritDoc
     */
    public function getConditionBuilder(array $parameters): ?PreparedStatementConditionBuilder
    {
        $this->readParameters($parameters);

        $conditionBuilder = new PreparedStatementConditionBuilder();
        $conditionBuilder->add('conversation_to_user.hideConversation IN (0,1)');
        if ($this->conversationID) {
            $conditionBuilder->add('conversation.conversationID = ?', [$this->conversationID]);
        }

        return $conditionBuilder;
    }

    /**
     * @inheritDoc
     */
    public function isAccessible(): bool
    {
        if (!WCF::getUser()->userID) {
            return false;
        }
        if (!MODULE_CONVERSATION) {
            return false;
        }

        return WCF::getSession()->getPermission('user.conversation.canUseConversation');
    }

    /**
     * @inheritDoc
     */
    public function getFormTemplateName(): string
    {
        if ($this->conversation) {
            return 'searchConversationMessage';
        }

        return '';
    }

    /**
     * @inheritDoc
     */
    public function assignVariables(): void
    {
        if (!empty($_REQUEST['conversationID'])) {
            $conversation = Conversation::getUserConversation(\intval($_REQUEST['conversationID']), WCF::getUser()->userID);
            if ($conversation !== null && $conversation->canRead()) {
                $this->conversation = $conversation;
                WCF::getTPL()->assign('searchedConversation', $conversation);
            }
        }
    }

    /**
     * @inheritDoc
     */
    private function readParameters(array $parameters): void
    {
        if (!empty($parameters['conversationID'])) {
            $this->conversationID = \intval($parameters['conversationID']);
        }
    }
}
