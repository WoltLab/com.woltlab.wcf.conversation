{include file='documentHeader'}

<head>
	<title>{$conversation->subject} {if $pageNo > 1}- {lang}wcf.page.pageNo{/lang} {/if} - {PAGE_TITLE|language}</title>
	
	{include file='headInclude'}
</head>

<body id="tpl{$templateName|ucfirst}">

{include file='header'}

<header class="boxHeadline marginTop">
	<hgroup>
		<h1><a href="{link controller='Conversation' object=$conversation}{/link}">{$conversation->subject}</a>{if $conversation->isClosed} <img src="{icon size='S'}lock{/icon}" alt="" title="{lang}wcf.conversation.closed{/lang}" class="jsTooltip jsIconLock icon16" />{/if}</h1>
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
	
	{hascontent}
	<nav>
		<ul>
			{content}
				{if !$conversation->isClosed}<li><a href="{link controller='ConversationMessageAdd' id=$conversationID}{/link}" title="{lang}wcf.conversation.message.add{/lang}" class="button buttonPrimary wbbThreadReply"><img src="{icon size='M'}addColored{/icon}" alt="" class="icon24" /> <span>{lang}wcf.conversation.message.button.add{/lang}</span></a></li>{/if}
				{event name='largeButtonsTop'}
			{/content}
		</ul>
	</nav>
	{/hascontent}
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
