{include file='documentHeader'}

<head>
	<title>{$conversation->subject} {if $pageNo > 1}- {lang}wcf.page.pageNo{/lang} {/if} - {PAGE_TITLE|language}</title>
	
	{include file='headInclude'}
	
	<script type="text/javascript" src="{@$__wcf->getPath()}js/WCF.Conversation.js"></script>
	<script type="text/javascript">
		//<![CDATA[
		$(function() {
			WCF.Language.addObject({
				'wcf.conversation.edit.assignLabel': '{lang}wcf.conversation.edit.assignLabel{/lang}',
				'wcf.conversation.edit.close': '{lang}wcf.conversation.edit.close{/lang}',
				'wcf.conversation.edit.leave': '{lang}wcf.conversation.edit.leave{/lang}',
				'wcf.conversation.edit.open': '{lang}wcf.conversation.edit.open{/lang}',
				'wcf.conversation.leave.title': '{lang}wcf.conversation.leave.title{/lang}',
				'wcf.global.state.closed': '{lang}wcf.global.state.closed{/lang}'
			});
			WCF.Icon.addObject({
				'wcf.icon.lock': '{icon size='S'}lock{/icon}'
			});
			
			var $availableLabels = [ {implode from=$labelList item=label}{ cssClassName: '{if $label->cssClassName}{@$label->cssClassName}{/if}', labelID: {@$label->labelID}, label: '{$label->label}' }{/implode} ];
			var $editorHandler = new WCF.Conversation.EditorHandlerConversation($availableLabels);
			var $inlineEditor = new WCF.Conversation.InlineEditor('.conversation');
			$inlineEditor.setEditorHandler($editorHandler);
		});
		//]]>
	</script>
</head>

<body id="tpl{$templateName|ucfirst}">

{include file='header'}

<header class="boxHeadline marginTop">
	<hgroup>
		<h1><a href="{link controller='Conversation' object=$conversation}{/link}">{$conversation->subject}</a>{if $conversation->isClosed} <img src="{icon size='S'}lock{/icon}" alt="" title="{lang}wcf.global.state.closed{/lang}" class="jsTooltip jsIconLock icon16" />{/if}
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

{* @todo: styling *}
<ul>
	{foreach from=$participants item=participant}
		<li class="box24">
			<a href="{link controller='User' object=$participant}{/link}" class="framed">{@$participant->getAvatar()->getImageTag(24)}</a>
			<hgroup>
				<h1><a href="{link controller='User' object=$participant}{/link}"{if $participant->hideConversation == 2} style="text-decoration: line-through"{/if}>{$participant->username}</a></h1>
				<h2><small>{@$participant->lastVisitTime|time}</small></h2>
			</hgroup>
		</li>
	{/foreach}
</ul>


<div class="contentNavigation">
	{pages print=true assign=pagesLinks controller='Conversation' object=$conversation link="pageNo=%d"}
	
	<nav>
		<ul class="conversation jsThreadInlineEditorContainer" data-conversation-id="{@$conversation->conversationID} data-label-ids="[ {implode from=$conversation->getAssignedLabels() item=label}{@$label->labelID}{/implode} ]" data-is-closed="{@$conversation->isClosed}" data-can-close-conversation="{if $conversation->userID == $__wcf->getUser()->userID}1{else}0{/if}"">
			<li><a class="button jsThreadInlineEditor"><img src="{icon size='M'}edit{/icon}" alt="" class="icon24" /> <span>{lang}wcf.global.button.edit{/lang}</span></a></li>
			{if !$conversation->isClosed}<li><a href="{link controller='ConversationMessageAdd' id=$conversationID}{/link}" title="{lang}wcf.conversation.message.add{/lang}" class="button buttonPrimary wbbThreadReply"><img src="{icon size='M'}addColored{/icon}" alt="" class="icon24" /> <span>{lang}wcf.conversation.message.button.add{/lang}</span></a></li>{/if}
			{event name='largeButtonsTop'}
		</ul>
	</nav>
</div>

<div class="marginTop">
	<ul class="wbbThreadPostList">
		{if $sortOrder == 'DESC'}{assign var='startIndex' value=$items-$startIndex+1}{/if}
		{include file='conversationMessageList'}
		{*if $thread->canReply()}{include file='threadQuickReply'}{/if*}
	</ul>
</div>

<div class="contentNavigation">
	{@$pagesLinks}
	
	{hascontent}
		<nav>
			<ul>
				{content}
					{if !$conversation->isClosed}<li><a href="{link controller='ConversationMessageAdd' id=$conversationID}{/link}" title="{lang}wcf.conversation.message.add{/lang}" class="button buttonPrimary wbbThreadReply"><img src="{icon size='M'}addColored{/icon}" alt="" class="icon24" /> <span>{lang}wcf.conversation.message.button.add{/lang}</span></a></li>{/if}
					{event name='largeButtonsBottom'}
				{/content}
			</ul>
		</nav>
	{/hascontent}
</div>

{include file='footer'}

</body>
</html>
