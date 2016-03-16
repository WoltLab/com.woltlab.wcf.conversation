{include file='documentHeader'}

<head>
	<title>{lang}wcf.conversation.message.add{/lang} - {$conversation->subject} - {PAGE_TITLE|language}</title>
	
	{include file='headInclude'}
	
	<script data-relocate="true" src="{@$__wcf->getPath()}js/WCF.Conversation{if !ENABLE_DEBUG_MODE}.min{/if}.js?v={@LAST_UPDATE_TIME}"></script>
	<script data-relocate="true">
		//<![CDATA[
		$(function() {
			WCF.Language.addObject({
				'wcf.message.bbcode.code.copy': '{lang}wcf.message.bbcode.code.copy{/lang}'
			});
			
			{include file='__messageQuoteManager' wysiwygSelector='text' supportPaste=true}
			new WCF.Conversation.Message.QuoteHandler($quoteManager);
			
			WCF.Message.Submit.registerButton('text', $('#messageContainer > .formSubmit > input[type=submit]'));
			new WCF.Message.FormGuard();
			new WCF.Message.BBCode.CodeViewer();
			
			WCF.System.Dependency.Manager.register('CKEditor', function() { new WCF.Message.UserMention('text'); });
		});
		//]]>
	</script>
</head>

<body id="tpl{$templateName|ucfirst}" data-template="{$templateName}" data-application="{$templateNameApplication}">
{include file='header'}

<header class="contentHeader">
	<h1 class="contentTitle">{lang}wcf.conversation.message.add{/lang}</h1>
</header>

{include file='userNotice'}

{if !$conversation->isDraft && !$conversation->hasOtherParticipants()}
	<p class="warning">{lang}wcf.conversation.noParticipantsWarning{/lang}</p>
{/if}

{include file='formError'}

<form id="messageContainer" class="jsFormGuard" method="post" action="{link controller='ConversationMessageAdd' id=$conversationID}{/link}">
	<section class="section">
		<h2 class="sectionTitle">{lang}wcf.conversation.message{/lang}</h2>
		
		<dl class="wide{if $errorField == 'text'} formError{/if}">
			<dt><label for="text">{lang}wcf.conversation.message{/lang}</label></dt>
			<dd>
				<textarea id="text" name="text" rows="20" cols="40" data-autosave="com.woltlab.wcf.conversation.messageAdd-{@$conversation->conversationID}">{$text}</textarea>
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
		<input type="submit" value="{lang}wcf.global.button.submit{/lang}" accesskey="s" />
		{include file='messageFormPreviewButton'}
		{@SECURITY_TOKEN_INPUT_TAG}
	</div>
</form>

<section class="section sectionContainerList">
	<h2 class="sectionTitle">{lang}wcf.conversation.message.add.previousPosts{/lang}{if $items != 1} <span class="badge">{#$items}</span>{/if}</h2>

	<ul class="messageList">
		{foreach from=$messages item=message}
			{assign var='objectID' value=$message->messageID}
			
			<li>
				<article class="message{if $message->getUserProfile()->userOnlineGroupID} userOnlineGroupMarking{@$message->getUserProfile()->userOnlineGroupID}{/if}" data-object-id="{@$message->messageID}">
					<div class="messageContent">
						<header class="messageHeader">
							<div class="box32 messageHeaderWrapper">
								<a href="{link controller='User' object=$message->getUserProfile()}{/link}">{@$message->getUserProfile()->getAvatar()->getImageTag(32)}</a>
								
								<div class="messageHeaderBox">
									<h2 class="messageTitle username"><a href="{link controller='User' object=$message->getUserProfile()}{/link}" class="userLink" data-user-id="{@$message->userID}">{$message->username}</a></h2>
									
									<ul class="messageHeaderMetaData">
										<li><a href="{link controller='Conversation' object=$conversation}messageID={@$message->messageID}{/link}#message{@$message->messageID}" class="permalink messagePublicationTime">{@$message->time|time}</a></li>
											
										{event name='messageHeaderMetaData'}
									</ul>
								</div>
							</div>
							
							{event name='messageHeader'}
						</header>
						
						<div class="messageBody">
							{event name='beforeMessageText'}
							
							<div class="messageText">
								{@$message->getFormattedMessage()}
							</div>
							
							{event name='afterMessageText'}
						</div>
					</div>
				</article>
			</li>
		{/foreach}
	</ul>
</section>

{include file='footer'}
{include file='wysiwyg'}

</body>
</html>