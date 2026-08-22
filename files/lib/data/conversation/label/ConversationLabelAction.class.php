<?php

namespace wcf\data\conversation\label;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\form\builder\field\BadgeColorFormField;
use wcf\system\WCF;

/**
 * Executes label-related actions.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractDatabaseObjectAction<ConversationLabel, ConversationLabelEditor>
 */
class ConversationLabelAction extends AbstractDatabaseObjectAction
{
    /**
     * @inheritDoc
     */
    protected $className = ConversationLabelEditor::class;

    /**
     * @inheritDoc
     */
    protected $permissionsDelete = ['user.conversation.canUseConversation'];

    /**
     * @inheritDoc
     */
    protected $permissionsUpdate = ['user.conversation.canUseConversation'];

    /**
     * @inheritDoc
     */
    public function validateUpdate()
    {
        parent::validateUpdate();

        if (isset($this->parameters['counters'])) {
            throw new PermissionDeniedException();
        }

        $label = $this->getSingleObject();
        if ($label->userID !== WCF::getUser()->userID) {
            throw new PermissionDeniedException();
        }

        // `DatabaseObjectEditor::update()` interpolates the array keys into the SQL,
        // therefore the writable columns must be restricted to the two editable ones.
        if (
            !isset($this->parameters['data'])
            || !\is_array($this->parameters['data'])
            || \array_diff(\array_keys($this->parameters['data']), ['label', 'cssClassName']) !== []
        ) {
            throw new UserInputException('data');
        }

        if (\array_key_exists('label', $this->parameters['data'])) {
            $this->readString('label', false, 'data');
        }

        if (\array_key_exists('cssClassName', $this->parameters['data'])) {
            $this->readString('cssClassName', true, 'data');

            // An empty string is the stored representation of the pseudo value 'none'.
            $cssClassNames = \array_diff(
                BadgeColorFormField::AVAILABLE_CSS_CLASSNAMES,
                [BadgeColorFormField::CUSTOM_CSS_CLASSNAME]
            );
            $cssClassNames[] = '';

            if (!\in_array($this->parameters['data']['cssClassName'], $cssClassNames, true)) {
                throw new UserInputException('cssClassName');
            }

            // 'none' is a pseudo value
            if ($this->parameters['data']['cssClassName'] === 'none') {
                $this->parameters['data']['cssClassName'] = '';
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function validateDelete()
    {
        parent::validateDelete();

        $label = $this->getSingleObject();
        if ($label->userID !== WCF::getUser()->userID) {
            throw new PermissionDeniedException();
        }
    }
}
