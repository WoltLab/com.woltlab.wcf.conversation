<?php

namespace wcf\system\form\builder\field;

use wcf\data\conversation\label\ConversationLabel;
use wcf\system\form\builder\field\validation\FormFieldValidationError;

/**
 * Implementation of a form field to select a conversation label.
 *
 * @author Olaf Braun
 * @copyright 2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
final class ConversationLabelFormField extends AbstractFormField
{
    /**
     * @var ConversationLabel[]
     */
    public array $labels = [];

    /**
     * @inheritDoc
     */
    protected $javaScriptDataHandlerModule = 'WoltLabSuite/Core/Form/Builder/Field/Value';

    /**
     * @inheritDoc
     */
    protected $templateName = 'shared_conversationLabelFormField';

    /**
     * @param ConversationLabel[] $labels
     */
    public function labels(array $labels): static
    {
        $this->labels = $labels;

        return $this;
    }

    /**
     * @return ConversationLabel[]
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    #[\Override]
    public function readValue()
    {
        if ($this->getDocument()->hasRequestData($this->getPrefixedId())) {
            $this->value = \intval($this->getDocument()->getRequestData($this->getPrefixedId()));
        }

        return $this;
    }

    #[\Override]
    public function validate()
    {
        if ($this->isRequired()) {
            if ($this->value <= 0) {
                $this->addValidationError(new FormFieldValidationError('empty'));
            }
        } elseif ($this->value > 0 && !\array_key_exists($this->value, $this->labels)) {
            $this->addValidationError(new FormFieldValidationError(
                'invalidValue',
                'wcf.global.form.error.noValidSelection'
            ));
        }
    }
}
