<?php

namespace wcf\system\form\builder\field;

use wcf\data\conversation\label\ConversationLabel;
use wcf\system\form\builder\field\validation\FormFieldValidationError;
use wcf\util\JSON;
use wcf\util\StringUtil;

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
     * `true` if the value `-1` can be selected for conversations without any label
     * @since 6.3
     */
    protected bool $invertible = false;

    /**
     * @inheritDoc
     */
    protected $javaScriptDataHandlerModule = 'WoltLabSuite/Core/Form/Builder/Field/Label';

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

    /**
     * Sets if the value `-1` can be selected for conversations without any label.
     *
     * @since 6.3
     */
    public function invertible(bool $invertible = true): static
    {
        $this->invertible = $invertible;

        return $this;
    }

    /**
     * Returns `true` if the value `-1` can be selected for conversations without any label.
     *
     * @since 6.3
     */
    public function isInvertible(): bool
    {
        return $this->invertible;
    }

    /**
     * Renders a `<woltlab-core-label-picker>` to select one of the labels.
     *
     * The selected value is `0` for no selection, `-1` for conversations without
     * any label (requires the field to be invertible) or a label id. Invalid
     * values are treated as no selection.
     *
     * @since 6.3
     */
    public function renderPicker(): string
    {
        $labels = [];
        foreach ($this->labels as $label) {
            $labels[] = [$label->labelID, $label->render()];
        }

        $value = (int)$this->getValue();
        if ($value === -1 ? !$this->isInvertible() : !\array_key_exists($value, $this->labels)) {
            $value = 0;
        }

        $attributes = [
            'name' => $this->getPrefixedId(),
            'id' => $this->getPrefixedId(),
            'labels' => JSON::encode($labels),
            'value' => $value,
        ];

        $html = '';
        foreach ($attributes as $attribute => $attributeValue) {
            $html .= \sprintf(' %s="%s"', $attribute, StringUtil::encodeHTML((string)$attributeValue));
        }
        if ($this->isRequired()) {
            $html .= ' required';
        }
        if ($this->isInvertible()) {
            $html .= ' invertible';
        }

        return "<woltlab-core-label-picker{$html}></woltlab-core-label-picker>";
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
        } elseif ($this->value === -1 && $this->isInvertible()) {
            return;
        } elseif (
            $this->value < 0
            || ($this->value > 0 && !\array_key_exists($this->value, $this->labels))
        ) {
            $this->addValidationError(new FormFieldValidationError(
                'invalidValue',
                'wcf.global.form.error.noValidSelection'
            ));
        }
    }
}
