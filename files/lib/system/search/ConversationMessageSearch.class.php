<?php

namespace wcf\system\search;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\message\SearchResultConversationMessage;
use wcf\data\conversation\message\SearchResultConversationMessageList;
use wcf\data\search\ISearchResultObject;
use wcf\system\cache\runtime\ConversationRuntimeCache;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * An implementation of ISearchProvider for searching in conversations.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ConversationMessageSearch extends AbstractSearchProvider
{
    private int $conversationID = 0;

    /**
     * searched conversation
     */
    private Conversation $conversation;

    /**
     * @var SearchResultConversationMessage[]
     */
    private array $messageCache = [];

    #[\Override]
    public function cacheObjects(array $objectIDs, ?array $additionalData = null): void
    {
        $messageList = new SearchResultConversationMessageList();
        $messageList->setObjectIDs($objectIDs);
        $messageList->readObjects();
        foreach ($messageList->getObjects() as $message) {
            $this->messageCache[$message->messageID] = $message;
        }
    }

    #[\Override]
    public function getAdditionalData(): array
    {
        return [
            'conversationID' => $this->conversationID,
        ];
    }

    #[\Override]
    public function getObject(int $objectID): ?ISearchResultObject
    {
        return $this->messageCache[$objectID] ?? null;
    }

    #[\Override]
    public function getJoins(): string
    {
        return "    JOIN        wcf1_conversation_to_user conversation_to_user
                    ON          conversation_to_user.participantID = " . WCF::getUser()->userID . "
                            AND conversation_to_user.conversationID = " . $this->getTableName() . ".conversationID
                    LEFT JOIN   wcf1_conversation conversation
                    ON          conversation.conversationID = " . $this->getTableName() . ".conversationID";
    }

    #[\Override]
    public function getTableName(): string
    {
        return 'wcf1_conversation_message';
    }

    #[\Override]
    public function getIDFieldName(): string
    {
        return $this->getTableName() . '.messageID';
    }

    #[\Override]
    public function getSubjectFieldName(): string
    {
        return 'conversation.subject';
    }

    #[\Override]
    public function getConditionBuilder(array $parameters): PreparedStatementConditionBuilder
    {
        $this->readParameters($parameters);

        $conditionBuilder = new PreparedStatementConditionBuilder();
        $conditionBuilder->add('conversation_to_user.hideConversation IN (0,1)');
        if ($this->conversationID !== 0) {
            $conditionBuilder->add('conversation.conversationID = ?', [$this->conversationID]);
        }

        return $conditionBuilder;
    }

    #[\Override]
    public function isAccessible(): bool
    {
        if (WCF::getUser()->isGuest()) {
            return false;
        }
        if (\MODULE_CONVERSATION === 0) {
            return false;
        }

        return WCF::getSession()->hasPermission('user.conversation.canUseConversation');
    }

    #[\Override]
    public function getFormTemplateName(): string
    {
        if (isset($this->conversation)) {
            return 'searchConversationMessage';
        }

        return '';
    }

    #[\Override]
    public function assignVariables(): void
    {
        $conversationID = (int)($_REQUEST['conversationID'] ?? 0);
        if ($conversationID !== 0) {
            $conversation = ConversationRuntimeCache::getInstance()->getObject($conversationID);
            if ($conversation !== null && $conversation->canRead()) {
                $this->conversation = $conversation;
                WCF::getTPL()->assign('searchedConversation', $conversation);
            }
        }
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function readParameters(array $parameters): void
    {
        $conversationID = (int)($parameters['conversationID'] ?? 0);
        if ($conversationID !== 0) {
            $this->conversationID = $conversationID;
        }
    }
}
