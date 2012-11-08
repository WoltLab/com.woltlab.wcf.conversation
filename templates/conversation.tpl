{include file='documentHeader'}

<head>
	<title>{$conversation->subject} {if $pageNo > 1}- {lang}wcf.page.pageNo{/lang} {/if} - {PAGE_TITLE|language}</title>
	
	{include file='headInclude'}
	
	<script type="text/javascript" src="{@$__wcf->getPath()}js/WCF.Conversation.js"></script>
	<script type="text/javascript" src="{@$__wcf->getPath()}js/WCF.Moderation.js"></script>
	<script type="text/javascript">
		//<![CDATA[
		$(function() {
			WCF.Language.addObject({
				'wcf.conversation.edit.addParticipants': '{lang}wcf.conversation.edit.addParticipants{/lang}',
				'wcf.conversation.edit.assignLabel': '{lang}wcf.conversation.edit.assignLabel{/lang}',
				'wcf.conversation.edit.close': '{lang}wcf.conversation.edit.close{/lang}',
				'wcf.conversation.edit.leave': '{lang}wcf.conversation.edit.leave{/lang}',
				'wcf.conversation.edit.open': '{lang}wcf.conversation.edit.open{/lang}',
				'wcf.conversation.leave.title': '{lang}wcf.conversation.leave.title{/lang}',
				'wcf.global.state.closed': '{lang}wcf.global.state.closed{/lang}'
			});
			WCF.Icon.addObject({
				'wcf.icon.lock': '{icon}lock{/icon}'
			});
			
			var $availableLabels = [ {implode from=$labelList item=label}{ cssClassName: '{if $label->cssClassName}{@$label->cssClassName}{/if}', labelID: {@$label->labelID}, label: '{$label->label}' }{/implode} ];
			var $editorHandler = new WCF.Conversation.EditorHandlerConversation($availableLabels);
			var $inlineEditor = new WCF.Conversation.InlineEditor('.conversation');
			$inlineEditor.setEditorHandler($editorHandler);
			
			new WCF.Conversation.Message.InlineEditor({@$conversation->conversationID});
			
			{include file='__messageQuoteManager' wysiwygSelector='text' supportPaste=true}
			new WCF.Conversation.Message.QuoteHandler($quoteManager);
			{if !$conversation->isClosed}new WCF.Conversation.QuickReply($quoteManager);{/if}
			
			new WCF.Moderation.Report.Content('com.woltlab.wcf.conversation.message', '.jsReportConversationMessage');
		});
		//]]>
	</script>
	
	{include file='imageViewer'}
</head>

<body id="tpl{$templateName|ucfirst}">

{include file='header'}

<header class="boxHeadline marginTop">
	<hgroup>
		<h1><a href="{link controller='Conversation' object=$conversation}{/link}">{$conversation->subject}</a>{if $conversation->isClosed} <img src="{icon}lock{/icon}" alt="" title="{lang}wcf.global.state.closed{/lang}" class="jsTooltip jsIconLock icon16" />{/if}
		{hascontent}
			<ul class="labelList">
				{content}
					{foreach from=$conversation->getAssignedLabels() item=label}
						<li><span class="label badge{if $label->cssClassName} {$label->cssClassName}{/if}">{lang}{$label->label}{/lang}</span></li>
					{/foreach}
				{/content}
			</ul>
		{/hascontent}
		</h1>
	</hgroup>
</header>

{include file='userNotice'}

{if !$conversation->isDraft}
	<div class="container containerPadding marginTop shadow">
		<fieldset>
			<legend>{lang}wcf.conversation.participants{/lang}</legend>
	
			<ul class="conversationParticipantList">
				{foreach from=$participants item=participant}
					<li class="box24">
						<a href="{link controller='User' object=$participant}{/link}" class="framed">{@$participant->getAvatar()->getImageTag(24)}</a>
						<hgroup>
							<h1><a href="{link controller='User' object=$participant}{/link}" class="userLink{if $participant->hideConversation == 2} conversationLeft{/if}" data-user-id="{@$participant->userID}">{$participant->username}</a></h1>
							<h2><dl class="plain inlineDataList">
								<dt>{lang}wcf.conversation.lastVisitTime{/lang}</dt>
								<dd>{if $participant->lastVisitTime}{@$participant->lastVisitTime|time}{else}-{/if}</dd>
							</dl></h2>
						</hgroup>
					</li>
				{/foreach}
			</ul>
		</fieldset>
	</div>
{/if}

<div class="contentNavigation">
	{pages print=true assign=pagesLinks controller='Conversation' object=$conversation link="pageNo=%d"}
	
	<nav>
		<ul class="conversation jsThreadInlineEditorContainer" data-conversation-id="{@$conversation->conversationID}" data-label-ids="[ {implode from=$conversation->getAssignedLabels() item=label}{@$label->labelID}{/implode} ]" data-is-closed="{@$conversation->isClosed}" data-can-close-conversation="{if $conversation->userID == $__wcf->getUser()->userID}1{else}0{/if}" data-can-add-participants="{if $conversation->canAddParticipants()}1{else}0{/if}">
			<li><a class="button jsThreadInlineEditor"><img src="{icon}edit{/icon}" alt="" class="icon24" /> <span>{lang}wcf.global.button.edit{/lang}</span></a></li>
			{if !$conversation->isClosed}<li><a href="{link controller='ConversationMessageAdd' id=$conversationID}{/link}" title="{lang}wcf.conversation.message.add{/lang}" class="button buttonPrimary jsQuickReply"><img src="{icon}addColored{/icon}" alt="" class="icon24" /> <span>{lang}wcf.conversation.message.button.add{/lang}</span></a></li>{/if}
			{event name='largeButtonsTop'}
		</ul>
	</nav>
</div>

<div class="marginTop">
	<ul class="messageList">
		{if $sortOrder == 'DESC'}{assign var='startIndex' value=$items-$startIndex+1}{/if}
		{include file='conversationMessageList'}
		{if !$conversation->isClosed}{include file='conversationQuickReply'}{/if}
	</ul>
</div>

<div class="contentNavigation">
	{@$pagesLinks}
	
	{hascontent}
		<nav>
			<ul>
				{content}
					{if !$conversation->isClosed}<li><a href="{link controller='ConversationMessageAdd' id=$conversationID}{/link}" title="{lang}wcf.conversation.message.add{/lang}" class="button buttonPrimary jsQuickReply"><img src="{icon}addColored{/icon}" alt="" class="icon24" /> <span>{lang}wcf.conversation.message.button.add{/lang}</span></a></li>{/if}
					{event name='largeButtonsBottom'}
				{/content}
			</ul>
		</nav>
	{/hascontent}
</div>

{include file='footer'}

</body>
</html>
