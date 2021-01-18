<?php

namespace wcf\system\search;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\message\SearchResultConversationMessage;
use wcf\data\conversation\message\SearchResultConversationMessageList;
use wcf\form\IForm;
use wcf\form\SearchForm;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * An implementation of ISearchableObjectType for searching in conversations.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package WoltLabSuite\Core\System\Search
 */
class ConversationMessageSearch extends AbstractSearchableObjectType
{
    /**
     * id of the searched conversation
     * @var integer
     */
    public $conversationID = 0;

    /**
     * searched conversation
     * @var Conversation
     */
    public $conversation;

    /**
     * message data cache
     * @var SearchResultConversationMessage[]
     */
    public $messageCache = [];

    /**
     * @inheritDoc
     */
    public function cacheObjects(array $objectIDs, ?array $additionalData = null)
    {
        $messageList = new SearchResultConversationMessageList();
        $messageList->setObjectIDs($objectIDs);
        $messageList->readObjects();
        foreach ($messageList->getObjects() as $message) {
            $this->messageCache[$message->messageID] = $message;
        }
    }

    /** @noinspection PhpMissingParentCallCommonInspection */

    /**
     * @inheritDoc
     */
    public function getAdditionalData()
    {
        return [
            'conversationID' => $this->conversationID,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getObject($objectID)
    {
        if (isset($this->messageCache[$objectID])) {
            return $this->messageCache[$objectID];
        }
    }

    /** @noinspection PhpMissingParentCallCommonInspection */

    /**
     * @inheritDoc
     */
    public function getJoins()
    {
        return "JOIN wcf" . WCF_N . "_conversation_to_user conversation_to_user ON (conversation_to_user.participantID = " . WCF::getUser()->userID . " AND conversation_to_user.conversationID = " . $this->getTableName() . ".conversationID)
			LEFT JOIN wcf" . WCF_N . "_conversation conversation ON (conversation.conversationID = " . $this->getTableName() . ".conversationID)";
    }

    /**
     * @inheritDoc
     */
    public function getTableName()
    {
        return 'wcf' . WCF_N . '_conversation_message';
    }

    /**
     * @inheritDoc
     */
    public function getIDFieldName()
    {
        return $this->getTableName() . '.messageID';
    }

    /** @noinspection PhpMissingParentCallCommonInspection */

    /**
     * @inheritDoc
     */
    public function getSubjectFieldName()
    {
        return 'conversation.subject';
    }

    /** @noinspection PhpMissingParentCallCommonInspection */

    /**
     * @inheritDoc
     */
    public function getConditions(?IForm $form = null)
    {
        $conditionBuilder = new PreparedStatementConditionBuilder();
        $conditionBuilder->add('conversation_to_user.hideConversation IN (0,1)');

        if (isset($_POST['conversationID'])) {
            $this->conversationID = \intval($_POST['conversationID']);

            $conditionBuilder->add('conversation.conversationID = ?', [$this->conversationID]);
        }

        return $conditionBuilder;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */

    /**
     * @inheritDoc
     */
    public function isAccessible()
    {
        if (!WCF::getUser()->userID) {
            return false;
        }
        if (!MODULE_CONVERSATION) {
            return false;
        }

        return WCF::getSession()->getPermission('user.conversation.canUseConversation');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */

    /**
     * @inheritDoc
     */
    public function getFormTemplateName()
    {
        if ($this->conversation) {
            return 'searchConversationMessage';
        }
    }

    /**
     * @inheritDoc
     */
    public function show(?IForm $form = null)
    {
        /** @var SearchForm $form */

        // get existing values
        if (
            $form !== null
            && isset($form->searchData['additionalData']['com.woltlab.wcf.conversation.message']['conversationID'])
        ) {
            $this->conversationID = $form->searchData['additionalData']['com.woltlab.wcf.conversation.message']['conversationID'];

            if ($this->conversationID) {
                $this->conversation = Conversation::getUserConversation($this->conversationID, WCF::getUser()->userID);

                if ($this->conversation === null || !$this->conversation->canRead()) {
                    $this->conversationID = 0;
                    $this->conversation = null;
                }
            }
        }

        WCF::getTPL()->assign('searchedConversation', $this->conversation);
    }
}
