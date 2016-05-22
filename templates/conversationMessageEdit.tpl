{capture assign='pageTitle'}{$__wcf->getActivePage()->getTitle()} - {$conversation->subject}{/capture}

{include file='header'}

{include file='formError'}

<form id="messageContainer" class="jsFormGuard" method="post" action="{link controller='ConversationMessageEdit' id=$messageID}{/link}">
	{if $isFirstMessage}
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
			
			<dl>
				<dt></dt>
				<dd>
					<label><input type="checkbox" name="participantCanInvite" id="participantCanInvite" value="1"{if $participantCanInvite} checked="checked"{/if} /> {lang}wcf.conversation.participantCanInvite{/lang}</label>
				</dd>
			</dl>
			
			{event name='participantFields'}
		</section>
	{/if}
	
	<section class="section">
		<h2 class="sectionTitle">{lang}wcf.conversation.message{/lang}</h2>
		
		<dl class="wide{if $errorField == 'text'} formError{/if}">
			<dt><label for="text">{lang}wcf.conversation.message{/lang}</label></dt>
			<dd>
				<textarea id="text" name="text" rows="20" cols="40" data-autosave="com.woltlab.wcf.conversation.messageEdit-{@$message->messageID}">{$text}</textarea>
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
		{if $isFirstMessage && $conversation->isDraft}<button name="draft" accesskey="d" value="1">{lang}wcf.conversation.button.saveAsDraft{/lang}</button>{/if}
		{include file='messageFormPreviewButton'}
		{@SECURITY_TOKEN_INPUT_TAG}
	</div>
</form>

{if $messages|count}
	<section class="section sectionContainerList">
		<h2 class="sectionTitle">{lang}wcf.conversation.message.add.previousPosts{/lang}</h2>
	
		<ul class="messageList">
			{foreach from=$messages item=message}
				{assign var='objectID' value=$message->messageID}
				
				<li>
					<article class="message{if $message->getUserProfile()->userOnlineGroupID} userOnlineGroupMarking{@$message->getUserProfile()->userOnlineGroupID}{/if}">
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
{/if}

<script data-relocate="true">
	//<![CDATA[
	$(function() {
		WCF.Language.addObject({
			'wcf.message.bbcode.code.copy': '{lang}wcf.message.bbcode.code.copy{/lang}'
		});
		
		{if $isFirstMessage && $conversation->isDraft}
		new WCF.Search.User('#participants', null, false, [ ], true);
		new WCF.Search.User('#invisibleParticipants', null, false, [ ], true);
		{/if}
		
		WCF.Message.Submit.registerButton('text', $('#messageContainer > .formSubmit > input[type=submit]'));
		new WCF.Message.FormGuard();
		new WCF.Message.BBCode.CodeViewer();
		
		WCF.System.Dependency.Manager.register('CKEditor', function() { new WCF.Message.UserMention('text'); });
	});
	//]]>
</script>

{include file='wysiwyg'}
{include file='footer'}
