<?php

namespace wcf\page;

use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\label\ConversationLabelList;
use wcf\data\conversation\UserConversationList;
use wcf\system\clipboard\ClipboardHandler;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\IllegalLinkException;
use wcf\system\page\PageLocationManager;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\ArrayUtil;
use wcf\util\HeaderUtil;

/**
 * Shows a list of conversations.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package WoltLabSuite\Core\Page
 *
 * @property    UserConversationList $objectList
 */
class ConversationListPage extends SortablePage
{
    /**
     * @inheritDoc
     */
    public $defaultSortField = CONVERSATION_LIST_DEFAULT_SORT_FIELD;

    /**
     * @inheritDoc
     */
    public $defaultSortOrder = CONVERSATION_LIST_DEFAULT_SORT_ORDER;

    /**
     * @inheritDoc
     */
    public $validSortFields = ['subject', 'time', 'username', 'lastPostTime', 'replies', 'participants'];

    /**
     * @inheritDoc
     */
    public $itemsPerPage = CONVERSATIONS_PER_PAGE;

    /**
     * @inheritDoc
     */
    public $loginRequired = true;

    /**
     * @inheritDoc
     */
    public $neededModules = ['MODULE_CONVERSATION'];

    /**
     * @inheritDoc
     */
    public $neededPermissions = ['user.conversation.canUseConversation'];

    /**
     * list filter
     * @var string
     */
    public $filter = '';

    /**
     * label id
     * @var int
     */
    public $labelID = 0;

    /**
     * label list object
     * @var ConversationLabelList
     */
    public $labelList;

    /**
     * number of conversations (no filter)
     * @var int
     */
    public $conversationCount = 0;

    /**
     * number of drafts
     * @var int
     */
    public $draftCount = 0;

    /**
     * number of hidden conversations
     * @var int
     */
    public $hiddenCount = 0;

    /**
     * number of sent conversations
     * @var int
     */
    public $outboxCount = 0;

    /**
     * participant that
     * @var string[]
     */
    public $participants = [];

    /**
     * @inheritDoc
     */
    public function readParameters()
    {
        parent::readParameters();

        if (isset($_REQUEST['filter'])) {
            $this->filter = $_REQUEST['filter'];
        }
        if (!\in_array($this->filter, UserConversationList::$availableFilters)) {
            $this->filter = '';
        }

        // user settings
        /** @noinspection PhpUndefinedFieldInspection */
        if (WCF::getUser()->conversationsPerPage) {
            /** @noinspection PhpUndefinedFieldInspection */
            $this->itemsPerPage = WCF::getUser()->conversationsPerPage;
        }

        // labels
        $this->labelList = ConversationLabel::getLabelsByUser();
        if (!empty($_REQUEST['labelID'])) {
            $this->labelID = \intval($_REQUEST['labelID']);

            $validLabel = false;
            foreach ($this->labelList as $label) {
                if ($label->labelID == $this->labelID) {
                    $validLabel = true;
                    break;
                }
            }

            if (!$validLabel) {
                throw new IllegalLinkException();
            }
        }

        if (isset($_REQUEST['participants'])) {
            $this->participants = \array_slice(ArrayUtil::trim(\explode(',', $_REQUEST['participants'])), 0, 20);
        }

        if (!empty($_POST)) {
            $participantsParameter = '';
            foreach ($this->participants as $participant) {
                if (!empty($participantsParameter)) {
                    $participantsParameter .= ',';
                }
                $participantsParameter .= \rawurlencode($participant);
            }
            if (!empty($participantsParameter)) {
                $participantsParameter = '&participants=' . $participantsParameter;
            }

            HeaderUtil::redirect(
                LinkHandler::getInstance()->getLink(
                    'ConversationList',
                    [],
                    'sortField=' . $this->sortField . '&sortOrder=' . $this->sortOrder . '&filter=' . $this->filter . '&labelID=' . $this->labelID . '&pageNo=' . $this->pageNo . $participantsParameter
                )
            );

            exit;
        }
    }

    /**
     * @inheritDoc
     */
    protected function initObjectList()
    {
        $this->objectList = new UserConversationList(WCF::getUser()->userID, $this->filter, $this->labelID);
        $this->objectList->setLabelList($this->labelList);

        if (!empty($this->participants)) {
            // The column `conversation_to_user.username` has no index, causing full table scans when
            // trying to filter by it, therefore we'll read the user ids in advance.
            $conditions = new PreparedStatementConditionBuilder();
            $conditions->add('username IN (?)', [$this->participants]);
            $sql = "SELECT  userID
                    FROM    wcf" . WCF_N . "_user
                    " . $conditions;
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($conditions->getParameters());
            $userIDs = [];
            while ($userID = $statement->fetchColumn()) {
                $userIDs[] = $userID;
            }

            if (!empty($userIDs)) {
                // The condition is split into two branches in order to account for invisible participants.
                // Invisible participants are only visible to the conversation starter and remain invisible
                // until the write their first message.
                //
                // We need to protect these users from being exposed as participants by including them for
                // any conversation that the current user has started. For all other conversations, users
                // flagged with `isInvisible = 0` must be excluded.
                //
                // See https://github.com/WoltLab/com.woltlab.wcf.conversation/issues/131
                $this->objectList->getConditionBuilder()->add('
                    (
                        (
                            conversation.userID = ?
                            AND conversation.conversationID IN (
                                SELECT      conversationID
                                FROM        wcf' . WCF_N . '_conversation_to_user
                                WHERE       participantID IN (?)
                                GROUP BY    conversationID
                                HAVING      COUNT(conversationID) = ?
                            )
                        )
                        OR
                        (
                            conversation.userID <> ?
                            AND conversation.conversationID IN (
                                SELECT      conversationID
                                FROM        wcf' . WCF_N . '_conversation_to_user
                                WHERE       participantID IN (?)
                                        AND isInvisible = ?
                                GROUP BY    conversationID
                                HAVING      COUNT(conversationID) = ?
                            )
                        )
                    )', [
                    // Parameters for the first condition.
                    WCF::getUser()->userID,
                    $userIDs,
                    \count($userIDs),

                    // Parameters for the second condition.
                    WCF::getUser()->userID,
                    $userIDs,
                    0,
                    \count($userIDs),
                ]);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function readData()
    {
        // if sort field is `username`, `conversation.` has to prepended because `username`
        // alone is ambiguous
        if ($this->sortField === 'username') {
            $this->sortField = 'conversation.username';
        }

        parent::readData();

        // change back to old value
        if ($this->sortField === 'conversation.username') {
            $this->sortField = 'username';
        }

        if ($this->filter != '') {
            // `-1` = pseudo object id to have to pages with identifier `com.woltlab.wcf.conversation.ConversationList`
            PageLocationManager::getInstance()->addParentLocation('com.woltlab.wcf.conversation.ConversationList', -1);
        }

        // read stats
        if (!$this->labelID && empty($this->participants)) {
            switch ($this->filter) {
                case '':
                    $this->conversationCount = $this->items;
                    break;

                case 'draft':
                    $this->draftCount = $this->items;
                    break;

                case 'hidden':
                    $this->hiddenCount = $this->items;
                    break;

                case 'outbox':
                    $this->outboxCount = $this->items;
                    break;
            }
        }

        if ($this->filter != '' || $this->labelID || !empty($this->participants)) {
            $conversationList = new UserConversationList(WCF::getUser()->userID, '');
            $this->conversationCount = $conversationList->countObjects();
        }
        if ($this->filter != 'draft' || $this->labelID || !empty($this->participants)) {
            $conversationList = new UserConversationList(WCF::getUser()->userID, 'draft');
            $this->draftCount = $conversationList->countObjects();
        }
        if ($this->filter != 'hidden' || $this->labelID || !empty($this->participants)) {
            $conversationList = new UserConversationList(WCF::getUser()->userID, 'hidden');
            $this->hiddenCount = $conversationList->countObjects();
        }
        if ($this->filter != 'outbox' || $this->labelID || !empty($this->participants)) {
            $conversationList = new UserConversationList(WCF::getUser()->userID, 'outbox');
            $this->outboxCount = $conversationList->countObjects();
        }
    }

    /**
     * @inheritDoc
     */
    public function assignVariables()
    {
        parent::assignVariables();

        WCF::getTPL()->assign([
            'filter' => $this->filter,
            'hasMarkedItems' => ClipboardHandler::getInstance()->hasMarkedItems(
                ClipboardHandler::getInstance()->getObjectTypeID('com.woltlab.wcf.conversation.conversation')
            ),
            'labelID' => $this->labelID,
            'labelList' => $this->labelList,
            'conversationCount' => $this->conversationCount,
            'draftCount' => $this->draftCount,
            'hiddenCount' => $this->hiddenCount,
            'outboxCount' => $this->outboxCount,
            'participants' => $this->participants,
            'validSortFields' => $this->validSortFields,
        ]);
    }
}
