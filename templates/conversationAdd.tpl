{include file='header'}

{include file='formError'}

<form id="messageContainer" class="jsFormGuard" method="post" action="{link controller='ConversationAdd'}{/link}">
	<section class="section">
		<h2 class="sectionTitle">{lang}wcf.conversation.information{/lang}</h2>
		
		<dl{if $errorField == 'subject'} class="formError"{/if}>
			<dt><label for="subject">{lang}wcf.global.subject{/lang}</label></dt>
			<dd>
				<input type="text" id="subject" name="subject" value="{$subject}" required="required" maxlength="255" class="long" />
				{if $errorField == 'subject'}
					<small class="innerError">
						{if $errorType == 'empty'}
							{lang}wcf.global.form.error.empty{/lang}
						{elseif $errorType == 'censoredWordsFound'}
							{lang}wcf.message.error.censoredWordsFound{/lang}
						{else}
							{lang}wcf.conversation.subject.error.{@$errorType}{/lang}
						{/if}
					</small>
				{/if}
			</dd>
		</dl>
		
		{event name='informationFields'}
	</section>
	
	<section class="section">
		<h2 class="sectionTitle">{lang}wcf.conversation.participants{/lang}</h2>
		
		<dl{if $errorField == 'participants'} class="formError"{/if}>
			<dt><label for="participants">{lang}wcf.conversation.participants{/lang}</label></dt>
			<dd>
				<input type="text" id="participants" name="participants" class="long" value="{$participants}">
				{if $errorField == 'participants'}
					<small class="innerError">
						{if $errorType == 'empty'}
							{lang}wcf.global.form.error.empty{/lang}
						{elseif $errorType|is_array}
							{foreach from=$errorType item='errorData'}
								{lang}wcf.conversation.participants.error.{@$errorData.type}{/lang}
							{/foreach}
						{else}
							{lang}wcf.conversation.participants.error.{@$errorType}{/lang}
						{/if}
					</small>
				{/if}
				<small>{lang}wcf.conversation.participants.description{/lang}</small>
			</dd>
		</dl>
		
		{if $__wcf->session->getPermission('user.conversation.canAddInvisibleParticipants')}
			<dl{if $errorField == 'invisibleParticipants'} class="formError"{/if}>
				<dt><label for="invisibleParticipants">{lang}wcf.conversation.invisibleParticipants{/lang}</label></dt>
				<dd>
					<input type="text" id="invisibleParticipants" name="invisibleParticipants" class="long" value="{$invisibleParticipants}">
					{if $errorField == 'invisibleParticipants'}
						<small class="innerError">
							{if $errorType == 'empty'}
								{lang}wcf.global.form.error.empty{/lang}
							{elseif $errorType|is_array}
								{foreach from=$errorType item='errorData'}
									{lang}wcf.conversation.participants.error.{@$errorData.type}{/lang}
								{/foreach}
							{else}
								{lang}wcf.conversation.participants.error.{@$errorType}{/lang}
							{/if}
						</small>
					{/if}
					<small>{lang}wcf.conversation.invisibleParticipants.description{/lang}</small>
				</dd>
			</dl>
		{/if}
		
		{if $__wcf->session->getPermission('user.conversation.canSetCanInvite')}
			<dl>
				<dt></dt>
				<dd>
					<label><input type="checkbox" name="participantCanInvite" id="participantCanInvite" value="1"{if $participantCanInvite} checked="checked"{/if} /> {lang}wcf.conversation.participantCanInvite{/lang}</label>
				</dd>
			</dl>
		{/if}
		
		{event name='participantFields'}
	</section>
		
	<section class="section">
		<h2 class="sectionTitle">{lang}wcf.conversation.message{/lang}</h2>
		
		<dl class="wide{if $errorField == 'text'} formError{/if}">
			<dt><label for="text">{lang}wcf.conversation.message{/lang}</label></dt>
			<dd>
				<textarea id="text" name="text" class="wysiwygTextarea"
				          data-autosave="com.woltlab.wcf.conversation.conversationAdd"
				          data-autosave-prompt="true"
				          data-support-mention="true"
				>{$text}</textarea>
				{if $errorField == 'text'}
					<small class="innerError">
						{if $errorType == 'empty'}
							{lang}wcf.global.form.error.empty{/lang}
						{elseif $errorType == 'tooLong'}
							{lang}wcf.message.error.tooLong{/lang}
						{elseif $errorType == 'censoredWordsFound'}
							{lang}wcf.message.error.censoredWordsFound{/lang}
						{elseif $errorType == 'disallowedBBCodes'}
							{lang}wcf.message.error.disallowedBBCodes{/lang}
						{else}
							{lang}wcf.conversation.message.error.{@$errorType}{/lang}
						{/if}
					</small>
				{/if}
			</dd>
		</dl>
		
		{event name='messageFields'}
	</section>
	
	{include file='messageFormTabs' wysiwygContainerID='text'}
	
	{event name='sections'}
	
	<div class="formSubmit">
		<input type="submit" value="{lang}wcf.global.button.submit{/lang}" accesskey="s">
		<button name="draft" accesskey="d" value="1">{lang}wcf.conversation.button.saveAsDraft{/lang}</button>
		{include file='messageFormPreviewButton'}
		{@SECURITY_TOKEN_INPUT_TAG}
	</div>
</form>

<script data-relocate="true">
	require(['WoltLab/WCF/Ui/ItemList/User'], function(UiItemListUser) {
		UiItemListUser.init('participants', {
			maxItems: {@$__wcf->getSession()->getPermission('user.conversation.maxParticipants')}
		});
		
		UiItemListUser.init('invisibleParticipants', {
			maxItems: {@$__wcf->getSession()->getPermission('user.conversation.maxParticipants')}
		});
	});
	
	$(function() {
		WCF.Message.Submit.registerButton('text', $('#messageContainer > .formSubmit > input[type=submit]'));
		new WCF.Message.FormGuard();
		
		{include file='__messageQuoteManager' wysiwygSelector='text' supportPaste=true}
	});
</script>

{include file='wysiwyg'}
{include file='footer'}
