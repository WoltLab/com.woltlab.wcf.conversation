{if $labelList|count}
	<fieldset>
		<legend>{lang}wcf.conversation.label.management.existingLabels{/lang}</legend>
	</fieldset>
{/if}

<fieldset>
	<legend>{lang}wcf.conversation.label.management.addLabel{/lang}</legend>
	
	<dl>
		<dt>{lang}wcf.conversation.label.labelName{/lang}</dt>
		<dd><input type="text" id="labelName" class="long" /></dd>
	</dl>
	<dl>
		<dt>{lang}wcf.conversation.label.cssClassName{/lang}</dt>
		<dd>
			<ul id="labelManagementList">
				{foreach from=$cssClassNames item=cssClassName}
					<li><label>
						<input type="radio" name="cssClassName" value="{@$cssClassName}"{if $cssClassName == 'none'} checked="checked"{/if} />
						<span class="badge label{if $cssClassName != 'none'} {@$cssClassName}{/if}">{lang}wcf.conversation.label.placeholder{/lang}</span>
					</label></li>
				{/foreach}
			</ul>
		</dd>
	</dl>
	
	<div class="formSubmit">
		<button id="addLabel">{lang}wcf.global.button.save{/lang}</button>
	</div>
</fieldset>