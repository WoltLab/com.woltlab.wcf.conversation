{include file='documentHeader'}

<head>
	<title>{lang}wcf.conversation.message.add{/lang} - {$conversation->subject} - {PAGE_TITLE|language}</title>
	
	{include file='headInclude'}
	
	<script data-relocate="true" src="{@$__wcf->getPath()}js/WCF.Conversation{if !ENABLE_DEBUG_MODE}.min{/if}.js?v={@$__wcfVersion}"></script>
	<script data-relocate="true">
		//<![CDATA[
		$(function() {
			{include file='__messageQuoteManager' wysiwygSelector='text' supportPaste=true}
			new WCF.Conversation.Message.QuoteHandler($quoteManager);
			
			WCF.Message.Submit.registerButton('text', $('#messageContainer > .formSubmit > input[type=submit]'));
			new WCF.Message.FormGuard();
			
			WCF.System.Dependency.Manager.register('CKEditor', function() { new WCF.Message.UserMention('text'); });
		});
		//]]>
	</script>
</head>

<body id="tpl{$templateName|ucfirst}">
{include file='header'}

<header class="boxHeadline">
	<h1>{lang}wcf.conversation.message.add{/lang}</h1>
</header>

{include file='userNotice'}

{if !$conversation->isDraft && (($conversation->userID == $__wcf->user->userID && $conversation->participants == 0) || (!$conversation->isInvisible && $conversation->participants == 1))}
	<p class="warning">{lang}wcf.conversation.noParticipantsWarning{/lang}</p>
{/if}

{include file='formError'}

<form id="messageContainer" class="jsFormGuard" method="post" action="{link controller='ConversationMessageAdd' id=$conversationID}{/link}">
	<div class="container containerPadding marginTop">
		<fieldset>
			<legend>{lang}wcf.conversation.message{/lang}</legend>
			
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
		</fieldset>
		
		{event name='fieldsets'}
		
		{include file='messageFormTabs' wysiwygContainerID='text'}
	</div>
	
	<div class="formSubmit">
		<input type="submit" value="{lang}wcf.global.button.submit{/lang}" accesskey="s" />
		{include file='messageFormPreviewButton'}
		{@SECURITY_TOKEN_INPUT_TAG}
	</div>
</form>

<header class="boxHeadline boxSubHeadline">
	<h2>{lang}wcf.conversation.message.add.previousPosts{/lang}</h2>
</header>

<div>
	<ul class="messageList">
		{foreach from=$messages item=message}
			{assign var='objectID' value=$message->messageID}
			
			<li class="marginTop">
				<article class="message messageReduced" data-object-id="{@$message->messageID}">
					<div>
						<section class="messageContent">
							<div>
								<header class="messageHeader">
									<div class="box32">
										<a href="{link controller='User' object=$message->getUserProfile()}{/link}" class="framed">{@$message->getUserProfile()->getAvatar()->getImageTag(32)}</a>
										
										<div class="messageHeadline">
											<p>
												<span class="username"><a href="{link controller='User' object=$message->getUserProfile()}{/link}" class="userLink" data-user-id="{@$message->userID}">{$message->username}</a></span>
												<a href="{link controller='Conversation' object=$conversation}messageID={@$message->messageID}{/link}#message{@$message->messageID}" class="permalink">{@$message->time|time}</a>
											</p>
										</div>
									</div>
								</header>
								
								<div class="messageBody">
									<div>
										<div class="messageText">
											{@$message->getFormattedMessage()}
										</div>
									</div>
									
									{include file='attachments'}
									
									<footer class="messageOptions">
										<nav class="jsMobileNavigation buttonGroupNavigation">
											<ul class="smallButtons buttonGroup"><li class="toTopLink"><a href="{$__wcf->getAnchor('top')}" title="{lang}wcf.global.scrollUp{/lang}" class="button jsTooltip"><span class="icon icon16 icon-arrow-up"></span> <span class="invisible">{lang}wcf.global.scrollUp{/lang}</span></a></li></ul>
										</nav>
									</footer>
								</div>
							</div>
						</section>
					</div>
				</article>
			</li>
		{/foreach}
	</ul>
</div>

{include file='footer'}
{include file='wysiwyg'}

</body>
</html>