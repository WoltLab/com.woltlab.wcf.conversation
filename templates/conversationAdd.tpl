{include file='documentHeader'}

<head>
	<title>{lang}wcf.conversation.add{/lang} - {PAGE_TITLE|language}</title>
	
	{include file='headInclude'}
	
	<script type="text/javascript">
		//<![CDATA[
		$(function() {
			new WCF.Search.User('#participants', null, false, [ ], true);
			new WCF.Search.User('#invisibleParticipants', null, false, [ ], true);
		});
		//]]>
	</script>
	
	{include file='imageViewer'}
</head>

<body id="tpl{$templateName|ucfirst}">
{include file='header'}

<header class="boxHeadline">
	<hgroup>
		<h1>{lang}wcf.conversation.add{/lang}</h1>
	</hgroup>
</header>

{include file='userNotice'}

{if $errorField}
	<p class="error">{lang}wcf.global.form.error{/lang}</p>
{/if}

<form id="messageContainer" method="post" action="{link controller='ConversationAdd'}{/link}">
	<div class="container containerPadding marginTop">
		<fieldset>
			<legend>{lang}wcf.conversation.information{/lang}</legend>
			
			<dl{if $errorField == 'subject'} class="formError"{/if}>
				<dt><label for="subject">{lang}wcf.global.subject{/lang}</label></dt>
				<dd>
					<input type="text" id="subject" name="subject" value="{$subject}" required="true" class="long" />
					{if $errorField == 'subject'}
						<small class="innerError">
							{if $errorType == 'empty'}
								{lang}wcf.global.form.error.empty{/lang}
							{else}
								{lang}wcf.conversation.subject.error.{@$errorType}{/lang}
							{/if}
						</small>
					{/if}
				</dd>
			</dl>
		</fieldset>
		
		<fieldset>
			<legend>{lang}wcf.conversation.participants{/lang}</legend>
			
			<dl{if $errorField == 'participants'} class="formError"{/if}>
				<dt><label for="participants">{lang}wcf.conversation.participants{/lang}</label></dt>
				<dd>
					<textarea id="participants" name="participants" class="long" cols="40" rows="2">{$participants}</textarea>
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
			
			<dl{if $errorField == 'invisibleParticipants'} class="formError"{/if}>
				<dt><label for="invisibleParticipants">{lang}wcf.conversation.invisibleParticipants{/lang}</label></dt>
				<dd>
					<textarea id="invisibleParticipants" name="invisibleParticipants" class="long" cols="40" rows="2">{$invisibleParticipants}</textarea>
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
			
			<dl>
				<dd>
					<label><input type="checkbox" name="participantCanInvite" id="participantCanInvite" value="1"{if $participantCanInvite} checked="checked"{/if} /> {lang}wcf.conversation.participantCanInvite{/lang}</label>
				</dd>
			</dl>
		</fieldset>
			
		<fieldset>
			<legend>{lang}wcf.conversation.message{/lang}</legend>
			
			<dl class="wide{if $errorField == 'text'} formError{/if}">
				<dt><label for="text">{lang}wcf.conversation.message{/lang}</label></dt>
				<dd>
					<textarea id="text" name="text" rows="20" cols="40">{$text}</textarea>
					{if $errorField == 'text'}
						<small class="innerError">
							{if $errorType == 'empty'}
								{lang}wcf.global.form.error.empty{/lang}
							{elseif $errorType == 'tooLong'}
								{lang}wcf.message.error.tooLong{/lang}
							{else}
								{lang}wcf.conversation.message.error.{@$errorType}{/lang}
							{/if}
						</small>
					{/if}
				</dd>
			</dl>
		</fieldset>
		
		{event name='fieldsets'}
		
		{include file='messageFormTabs'}
	</div>
	
	<div class="formSubmit">
		<input type="submit" value="{lang}wcf.global.button.submit{/lang}" accesskey="s" />
		<button name="draft" accesskey="d" value="1">{lang}wcf.conversation.button.saveAsDraft{/lang}</button>
		{include file='messageFormPreviewButton'}
	</div>
</form>

{include file='footer'}
{include file='wysiwyg'}

</body>
</html>