<ul class="labelList jsOnly">
	<li class="dropdown labelChooser">
		<div class="dropdownToggle">
			<span class="badge label">{lang}wcf.label.none{/lang}</span>
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
		{foreach from=$field->getLabels() item=label}
			<option value="{$label->labelID}">{$label->label}</option>
		{/foreach}
	</select>
</noscript>

<script data-relocate="true">
	require(['Language', 'WoltLabSuite/Core/Form/Builder/Field/Controller/Label'], (Language, FormBuilderFieldLabel) => {
		Language.addObject({
			'wcf.label.none': '{jslang}wcf.label.none{/jslang}',
			'wcf.label.withoutSelection': '{jslang}wcf.label.withoutSelection{/jslang}'
		});

		new FormBuilderFieldLabel(
			'{unsafe:$field->getPrefixedId()|encodeJS}',
			{if $field->getValue()}'{unsafe:$field->getValue()|encodeJS}'{else}null{/if},
		);
	});
</script>
