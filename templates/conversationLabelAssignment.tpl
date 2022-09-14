<ul>
	{foreach from=$labelList item=label}
		<li>
			<label>
				<input type="checkbox"{if $label->labelID|in_array:$assignedLabels} checked{/if} data-label-id="{@$label->labelID}">
				<span class="badge label{if $label->cssClassName} {@$label->cssClassName}{/if}">{$label->label}</span>
			</label>
		</li>
	{/foreach}
</ul>

<div class="formSubmit">
	<button type="button" class="button buttonPrimary" id="assignLabels">{lang}wcf.global.button.save{/lang}</button>
</div>
