{capture assign='pageTitle'}{if $filter}{lang}wcf.conversation.folder.{$filter}{/lang}{else}{$__wcf->getActivePage()->getTitle()}{/if}{/capture}

{capture assign='contentHeader'}
	<header class="contentHeader">
		<div class="contentHeaderTitle">
			<h1 class="contentTitle">{if $filter}{lang}wcf.conversation.folder.{$filter}{/lang}{else}{$__wcf->getActivePage()->getTitle()}{/if}</h1>
		</div>
		
		{hascontent}
			<nav class="contentHeaderNavigation">
				<ul>
					{content}
						{if $__wcf->session->getPermission('user.conversation.canStartConversation')}
							<li>
								<a href="{link controller='ConversationAdd'}{/link}" title="{lang}wcf.conversation.add{/lang}" class="button buttonPrimary">
									{icon name='plus'}
									<span>{lang}wcf.conversation.button.add{/lang}</span>
								</a>
							</li>
						{/if}
						{event name='contentHeaderNavigation'}
					{/content}
				</ul>
			</nav>
		{/hascontent}
	</header>
{/capture}

{capture append='headContent'}
	<link rel="alternate" type="application/rss+xml" title="{lang}wcf.global.button.rss{/lang}" href="{link controller='ConversationRssFeed' at=$__wcf->user->getAccessToken()}{/link}">
{/capture}

{capture assign='sidebarRight'}
	<section class="box">
		<h2 class="boxTitle">{lang}wcf.conversation.folders{/lang}</h2>
		
		<div class="boxContent">
			<nav>
				<ol class="boxMenu">
					<li{if $filter == ''} class="active"{/if}>
						<a class="boxMenuLink" href="{link controller='ConversationList'}{/link}"><span class="boxMenuLinkTitle">{lang}wcf.conversation.conversations{/lang}</span>{if $conversationCount} <span class="badge">{#$conversationCount}</span>{/if}</a>
					</li>
					<li{if $filter == 'draft'} class="active"{/if}>
						<a class="boxMenuLink" href="{link controller='ConversationList'}filter=draft{/link}"><span class="boxMenuLinkTitle">{lang}wcf.conversation.folder.draft{/lang}</span>{if $draftCount} <span class="badge">{#$draftCount}</span>{/if}</a>
					</li>
					<li{if $filter == 'outbox'} class="active"{/if}>
						<a class="boxMenuLink" href="{link controller='ConversationList'}filter=outbox{/link}"><span class="boxMenuLinkTitle">{lang}wcf.conversation.folder.outbox{/lang}</span>{if $outboxCount} <span class="badge">{#$outboxCount}</span>{/if}</a>
					</li>
					<li{if $filter == 'hidden'} class="active"{/if}>
						<a class="boxMenuLink" href="{link controller='ConversationList'}filter=hidden{/link}"><span class="boxMenuLinkTitle">{lang}wcf.conversation.folder.hidden{/lang}</span>{if $hiddenCount} <span class="badge">{#$hiddenCount}</span>{/if}</a>
					</li>
				</ol>
			</nav>
		</div>
	</section>

	{event name='beforeQuotaBox'}
	
	<section class="box conversationQuota">
		<h2 class="boxTitle">{lang}wcf.conversation.quota{/lang}</h2>
		
		<div class="boxContent">
			{assign var='conversationCount' value=$__wcf->getConversationHandler()->getConversationCount()}
			{assign var='maxConversationCount' value=$__wcf->session->getPermission('user.conversation.maxConversations')}
			{if $maxConversationCount == 0}
				{assign var='maxConversationCount' value=1}
			{/if}
			{assign var='conversationCountValue' value=$conversationCount/$maxConversationCount*100}
			<meter class="conversationQuotaMeter" min="0" max="100" low="90" high="99" value="{$conversationCountValue|ceil}" aria-label="{lang}wcf.conversation.quota{/lang}">
				{#$conversationCountValue}&nbsp;%
			</meter>
			<p><small>{lang}wcf.conversation.quota.description{/lang}</small></p>
		</div>
	</section>
	
	{event name='boxes'}
{/capture}

{capture assign='contentInteractionButtons'}
	<button type="button" class="markAllAsReadButton contentInteractionButton button small jsOnly">{icon name='check'} <span>{lang}wcf.global.button.markAllAsRead{/lang}</span></button>

	<a href="{link controller="ConversationLabelList"}{/link}" class="button contentInteractionButton small">{icon name='tags'} <span>{lang}wcf.conversation.label.management{/lang}</span></a>
{/capture}

{capture assign='contentInteractionDropdownItems'}
	<li><a rel="alternate" href="{link controller='ConversationRssFeed' at=$__wcf->user->getAccessToken()}{/link}">{lang}wcf.global.button.rss{/lang}</a></li>
{/capture}

{include file='header'}

<div class="section {$listView->getContainerCssClassName()}">
	{unsafe:$listView->render()}
</div>

<script data-relocate="true">
	require(['WoltLabSuite/Core/Conversation/Component/MarkAllAsRead'], ({ setup }) => {
		setup(document.getElementById('{unsafe:$listView->getID()|encodeJS}_items'));
	});
</script>

{include file='footer'}
