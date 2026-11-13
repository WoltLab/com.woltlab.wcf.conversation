<ul class="labelList jsOnly">
	<li class="dropdown labelChooser">
		<div class="dropdownToggle">
			<span class="labelBadge">{lang}wcf.label.none{/lang}</span>
		</div>
		<div class="dropdownMenu">
			<ul class="scrollableDropdownMenu">
				{foreach from=$field->getLabels() item=label}
					<li data-label-id="{$label->labelID}">
						<span>{unsafe:$label->render()}</span>
					</li>
				{/foreach}
			</ul>
		</div>
	</li>
</ul>

<noscript>
	<select name="{$field->getPrefixedId()}">
		<option value="0">{lang}wcf.label.none{/lang}</option>
		{if $field->isInvertible()}
			<option value="-1"{if $field->getValue() == -1} selected{/if}>{lang}wcf.label.withoutSelection{/lang}</option>
		{/if}
		{foreach from=$field->getLabels() item=label}
			<option value="{$label->labelID}"{if $field->getValue() == $label->labelID} selected{/if}>{$label->label}</option>
		{/foreach}
	</select>
</noscript>

<script data-relocate="true">
	require(['WoltLabSuite/Core/Form/Builder/Field/Controller/Label'], (FormBuilderFieldLabel) => {
		{jsphrase name='wcf.label.none'}
		{jsphrase name='wcf.label.withoutSelection'}

		new FormBuilderFieldLabel(
			'{unsafe:$field->getPrefixedId()|encodeJS}',
			{if $field->getValue()}'{unsafe:$field->getValue()|encodeJS}'{else}null{/if},
			{
				showWithoutSelection: {if $field->isInvertible()}true{else}false{/if},
			},
		);
	});
</script>
