<?php

namespace wcf\system\event\listener;

use wcf\acp\form\UserGroupAddForm;
use wcf\acp\form\UserGroupEditForm;
use wcf\data\user\group\UserGroup;
use wcf\system\WCF;

/**
 * Handles 'canBeAddedAsConversationParticipant' setting.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class UserGroupAddCanBeAddedAsConversationParticipantListener implements IParameterizedEventListener
{
    /**
     * instance of UserGroupAddForm
     * @var UserGroupAddForm|UserGroupEditForm
     */
    protected $eventObj;

    /**
     * true if group can be added as participant
     * @var int
     */
    protected $canBeAddedAsConversationParticipant = 0;

    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        $this->eventObj = $eventObj;

        if ($this->eventObj instanceof UserGroupEditForm && $this->eventObj->group !== null) {
            switch ($this->eventObj->group->groupType) {
                case UserGroup::EVERYONE:
                case UserGroup::GUESTS:
                case UserGroup::USERS:
                    return;
            }
        }

        $this->{$eventName}();
    }

    /**
     * Handles the assignVariables event.
     *
     * @return void
     */
    protected function assignVariables()
    {
        WCF::getTPL()->assign([
            'canBeAddedAsConversationParticipant' => $this->canBeAddedAsConversationParticipant,
        ]);
    }

    /**
     * Handles the readData event.
     *
     * @return void
     */
    protected function readData()
    {
        \assert($this->eventObj instanceof UserGroupEditForm);

        if ($_POST === []) {
            // @phpstan-ignore property.notFound
            $this->canBeAddedAsConversationParticipant = $this->eventObj->group->canBeAddedAsConversationParticipant;
        }
    }

    /**
     * Handles the readFormParameters event.
     *
     * @return void
     */
    protected function readFormParameters()
    {
        if (isset($_POST['canBeAddedAsConversationParticipant'])) {
            $this->canBeAddedAsConversationParticipant = \intval($_POST['canBeAddedAsConversationParticipant']);
        }
    }

    /**
     * Handles the save event.
     *
     * @return void
     */
    protected function save()
    {
        $this->eventObj->additionalFields = \array_merge($this->eventObj->additionalFields, [
            'canBeAddedAsConversationParticipant' => $this->canBeAddedAsConversationParticipant,
        ]);
    }
}
