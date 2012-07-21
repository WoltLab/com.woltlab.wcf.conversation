<fieldset>
	<legend>{lang}wcf.conversation.label.assignment{/lang}</legend>
	
	<ul>
		{foreach from=$labelList item=label}
			<li><label>
				<input type="checkbox"{if $label->labelID|in_array:$assignedLabels} checked="checked"{/if} data-label-id="{@$label->labelID}" />
				<span class="badge label{if $label->cssClassName} {@$label->cssClassName}{/if}">{$label->label}</span>
			</li></label>
		{/foreach}
	</ul>
</fieldset>

<div class="formSubmit">
	<button id="assignLabels">{lang}wcf.global.button.save{/lang}</button>
</div>