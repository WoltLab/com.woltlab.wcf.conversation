{include file='documentHeader'}

<head>
	<title>{lang}wcf.conversation.message.add{/lang} - {$conversation->subject} - {PAGE_TITLE|language}</title>
	
	{include file='headInclude'}
	
	<script type="text/javascript" src="{@$__wcf->getPath()}js/WCF.Conversation.js"></script>
	<script type="text/javascript">
		//<![CDATA[
		$(function() {
			{include file='__messageQuoteManager' wysiwygSelector='text' supportPaste=true}
			new WCF.Conversation.Message.QuoteHandler($quoteManager);
			
			WCF.Message.Submit.registerButton('text', $('#messageContainer > .formSubmit > input[type=submit]'));
		});
		//]]>
	</script>
	
	{include file='imageViewer'}
</head>

<body id="tpl{$templateName|ucfirst}">
{include file='header'}

<header class="boxHeadline">
	<hgroup>
		<h1>{lang}wcf.conversation.message.add{/lang}</h1>
	</hgroup>
</header>

{include file='userNotice'}

{if $errorField}
	<p class="error">{lang}wcf.global.form.error{/lang}</p>
{/if}

<form id="messageContainer" method="post" action="{link controller='ConversationMessageAdd' id=$conversationID}{/link}">
	<div class="container containerPadding marginTop">
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
		
		{include file='messageFormTabs' wysiwygContainerID='text'}
	</div>
	
	<div class="formSubmit">
		<input type="submit" value="{lang}wcf.global.button.submit{/lang}" accesskey="s" />
		{include file='messageFormPreviewButton'}
	</div>
</form>

<header class="boxHeadline">
	<hgroup>
		<h1>{lang}wcf.conversation.message.add.previousPosts{/lang}</h1>
	</hgroup>
</header>

<div>
	<ul class="messageList">
		{assign var='startIndex' value=$items}
		{foreach from=$messages item=message}
			{assign var='objectID' value=$message->messageID}
			
			<li class="marginTop">
				<article class="message messageReduced" data-object-id="{@$message->messageID}">
					<div>
						<section class="messageContent">
							<div>
								<header class="messageHeader">
									<p class="messageCounter"><a href="{link controller='Conversation' object=$conversation}messageID={@$message->messageID}{/link}#message{@$message->messageID}" title="{lang}wcf.conversation.message.permalink{/lang}" class="button jsTooltip">{#$startIndex}</a></p>
									
									<div class="box32">
										<a href="{link controller='User' object=$message->getUserProfile()}{/link}" class="framed">{@$message->getUserProfile()->getAvatar()->getImageTag(32)}</a>
										
										<hgroup class="messageHeadline">
											<h2>
												<span class="username"><a href="{link controller='User' object=$message->getUserProfile()}{/link}" class="userLink" data-user-id="{@$message->userID}">{$message->username}</a></span>
												{@$message->time|time}
											</h2>
										</hgroup>
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
										<nav>
											<ul class="smallButtons buttonGroup"><li class="toTopLink"><a href="{@$__wcf->getAnchor('top')}" title="{lang}wcf.global.scrollUp{/lang}" class="button jsTooltip"><span class="icon icon16 icon-arrow-up"></span> <span class="invisible">{lang}wcf.global.scrollUp{/lang}</span></a></li></ul>
										</nav>
									</footer>
								</div>
							</div>
						</section>
					</div>
				</article>
			</li>
			
			{assign var='startIndex' value=$startIndex-1}
		{/foreach}
	</ul>
</div>

{include file='footer'}
{include file='wysiwyg'}

</body>
</html>