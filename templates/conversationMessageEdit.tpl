{include file='documentHeader'}

<head>
	<title>{lang}wcf.conversation.message.edit{/lang} - {$conversation->subject} - {PAGE_TITLE|language}</title>
	
	{include file='headInclude'}
	
	<script type="text/javascript">
		//<![CDATA[
		$(function() {
			new WCF.Message.DefaultPreview();
		});
		//]]>
	</script>
</head>

<body id="tpl{$templateName|ucfirst}">
{include file='header'}

<header class="boxHeadline">
	<hgroup>
		<h1>{lang}wcf.conversation.message.edit{/lang}</h1>
	</hgroup>
</header>

{include file='userNotice'}

{if $errorField}
	<p class="error">{lang}wcf.global.form.error{/lang}</p>
{/if}

<form id="messageContainer" method="post" action="{link controller='ConversationMessageEdit' id=$messageID}{/link}">
	<div class="container containerPadding marginTop shadow">
		{if $isFirstMessage}
			<fieldset>
				<legend>{lang}wcf.conversation.information{/lang}</legend>
				
				{if $conversation->isDraft}
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
				{/if}
				
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
		{/if}
		
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
		{if $isFirstMessage && $conversation->isDraft}<button name="draft" accesskey="d" value="1">{lang}wcf.conversation.button.saveAsDraft{/lang}</button>{/if}
		<button id="previewButton" class="javascriptOnly" accesskey="p">{lang}wcf.global.button.preview{/lang}</button>
	</div>
</form>

{if $messages|count}
	<header class="boxHeadline">
		<hgroup>
			<h1>{lang}wcf.conversation.message.add.previousPosts{/lang}</h1>
		</hgroup>
	</header>
	
	<div>
		<ul class="wbbThreadPostList">
			{assign var='startIndex' value=$items}
			{foreach from=$messages item=message}
				{assign var='objectID' value=$message->messageID}
				
				<li class="marginTop shadow">
					<article class="message messageReduced">
						<div>
							<section class="messageContent">
								<div>
									<header class="messageHeader">
										<p class="messageCounter"><a href="{link controller='Conversation' object=$conversation}messageID={@$message->messageID}{/link}#message{@$message->messageID}" title="{lang}wcf.conversation.message.permalink{/lang}" class="button jsTooltip">{#$startIndex}</a></p>
										
										<div class="messageCredits box32">
											{if $message->getUserProfile()->getAvatar()}
												<a href="{link controller='User' object=$message->getUserProfile()}{/link}" class="framed">{@$message->getUserProfile()->getAvatar()->getImageTag(32)}</a>
											{/if}
											<div>
												<p><a href="{link controller='User' object=$message->getUserProfile()}{/link}" class="userLink" data-user-id="{@$message->userID}">{$message->username}</a><p>
												
												{@$message->time|time}
											</div>
										</div>
									</header>
									
									<div class="messageBody">
										<div>
											<div class="messageText">
												{@$message->getFormattedMessage()}
											</div>
											
											{include file='attachments'}
										</div>
										
										<footer class="contentOptions clearfix">
											<nav>
												<ul class="smallButtons">
													<li class="toTopLink"><a href="{@$__wcf->getAnchor('top')}" title="{lang}wcf.global.scrollUp{/lang}" class="button jsTooltip"><img src="{icon size='S'}circleArrowUp{/icon}" alt="" class="icon16" /> <span class="invisible">{lang}wcf.global.scrollUp{/lang}</span></a></li>
												</ul>
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
{/if}

{include file='footer'}
{include file='wysiwyg'}

</body>
</html>