{include file='documentHeader'}

<head>
	<title>{lang}wcf.conversation.conversations{/lang} {if $pageNo > 1}- {lang}wcf.page.pageNo{/lang} {/if}- {PAGE_TITLE|language}</title>
	
	{include file='headInclude' sandbox=false}
</head>

<body id="tpl{$templateName|ucfirst}">

{capture assign='sidebar'}
	<nav id="sidebarContent" class="sidebarContent">
		<ul>
			<li class="menuGroup">
				<h1>{lang}wcf.conversation.folders{/lang}</h1>
				<div class="menuGroupItems">
					<ul>
						<li{if $filter == ''} class="active"{/if}><a href="{link controller='ConversationList'}{/link}">{lang}wcf.conversation.conversations{/lang}</a></li>
						<li{if $filter == 'draft'} class="active"{/if}><a href="{link controller='ConversationList'}filter=draft{/link}">{lang}wcf.conversation.folder.draft{/lang}</a></li>
						<li{if $filter == 'outbox'} class="active"{/if}><a href="{link controller='ConversationList'}filter=outbox{/link}">{lang}wcf.conversation.folder.outbox{/lang}</a></li>
						<li{if $filter == 'hidden'} class="active"{/if}><a href="{link controller='ConversationList'}filter=hidden{/link}">{lang}wcf.conversation.folder.hidden{/lang}</a></li>
					</ul>
				</div>
			</li>
		</ul>
	</nav>	
{/capture}

{include file='header' sandbox=false sidebarOrientation='left'}

<header class="boxHeadline">
	<hgroup>
		<h1>{if $filter}{lang}wcf.conversation.folder.{$filter}{/lang}{else}{lang}wcf.conversation.conversations{/lang}{/if}</h1>
	</hgroup>
</header>

{include file='userNotice'}

<div class="contentNavigation">
	{pages print=true assign=pagesLinks controller='ConversationList' link="filter=$filter&pageNo=%d&sortField=$sortField&sortOrder=$sortOrder"}
	
	{hascontent}
		<nav>
			<ul>
				{content}
					<li><a href="{link controller='ConversationAdd'}{/link}" title="{lang}wcf.conversation.add{/lang}" class="button"><img src="{icon size='M'}asterisk{/icon}" alt="" class="icon24" /> <span>{lang}wcf.conversation.button.add{/lang}</span></a></li>
					{event name='largeButtonsTop'}
				{/content}
			</ul>
		</nav>
	{/hascontent}
</div>

{if !$items}
	<p class="info">{lang}wcf.conversation.noConversations{/lang}</p>
{else}
	<div class="marginTop tabularBox tabularBoxTitle shadow wbbThreadList"> {* @todo: use generic css class*}
		<hgroup>
			<h1>{lang}wcf.conversation.conversations{/lang} <span class="badge badgeInverse">{#$items}</span></h1>
		</hgroup>
		
		<table class="table">
			<thead>
				<tr>
					<th colspan="2" class="columnTitle columnSubject{if $sortField == 'subject'} active{/if}"><a href="{link controller='ConversationList'}{if $filter}filter={@$filter}&{/if}pageNo={@$pageNo}&sortField=subject&sortOrder={if $sortField == 'subject' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{/link}">{lang}wcf.conversation.subject{/lang}{if $sortField == 'subject'} <img src="{icon size='S'}sort{@$sortOrder}{/icon}" alt="" />{/if}</a></th>
					<th class="columnDigits columnReplies{if $sortField == 'replies'} active{/if}"><a href="{link controller='ConversationList'}{if $filter}filter={@$filter}&{/if}pageNo={@$pageNo}&sortField=replies&sortOrder={if $sortField == 'replies' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{/link}">{lang}wcf.conversation.replies{/lang}{if $sortField == 'replies'} <img src="{icon size='S'}sort{@$sortOrder}{/icon}" alt="" />{/if}</a></th>
					<th class="columnDigits columnParticipants{if $sortField == 'participants'} active{/if}"><a href="{link controller='ConversationList'}{if $filter}filter={@$filter}&{/if}pageNo={@$pageNo}&sortField=participants&sortOrder={if $sortField == 'participants' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{/link}">{lang}wcf.conversation.participants{/lang}{if $sortField == 'participants'} <img src="{icon size='S'}sort{@$sortOrder}{/icon}" alt="" />{/if}</a></th>
					<th class="columnText columnLastPost{if $sortField == 'lastPostTime'} active{/if}"><a href="{link controller='ConversationList'}{if $filter}filter={@$filter}&{/if}pageNo={@$pageNo}&sortField=lastPostTime&sortOrder={if $sortField == 'lastPostTime' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{/link}">{lang}wcf.conversation.lastPostTime{/lang}{if $sortField == 'lastPostTime'} <img src="{icon size='S'}sort{@$sortOrder}{/icon}" alt="" />{/if}</a></th>
				</tr>
			</thead>
			
			<tbody>
				{foreach from=$objects item=conversation}
					<tr class="wbbThread {if $conversation->isNew()} new{/if}">
						<td class="columnIcon columnAvatar">
							{if $conversation->getUserProfile()->getAvatar()}
								<div>
									<p class="framed">{@$conversation->getUserProfile()->getAvatar()->getImageTag(32)}</p>
									
									{if $conversation->ownPosts && $conversation->userID != $__wcf->user->userID}
										{if $__wcf->getUserProfileHandler()->getAvatar()}
											<small class="framed myAvatar" title="{lang}wcf.conversation.ownPosts{/lang}">{@$__wcf->getUserProfileHandler()->getAvatar()->getImageTag(16)}</small>
										{/if}
									{/if}
								</div>
							{/if}
						</td>
						<td class="columnText columnTopic">
							<h1>
								{* @todo: if $thread->hasLabels()}
									<ul class="labelList">
										{foreach from=$thread->getLabels() item=label}
											<li><a href="#" class="badge label{if $label->cssClassName} {$label->cssClassName}{/if}">{lang}{$label->label}{/lang}</a></li>
										{/foreach}
									</ul>
								{/if*}
								
								{if $conversation->isNew()}
									<a href="{link controller='Conversation' object=$conversation}action=firstNew{/link}" class="jsTooltip" title="{lang}wcf.conversation.gotoFirstNewPost{/lang}"><img src="{icon size='S'}circleArrowDown{/icon}" alt="" class="wbbFirstNewPost icon16" /></a>
								{/if}
								
								<a href="{link controller='Conversation' object=$conversation}{/link}" class="conversationLink" data-conversation-id="{@$conversation->threadID}">{$conversation->subject}</a>
							</h1>
							
							<aside class="statusDisplay">
								{smallpages pages=$conversation->getPages() controller='Conversation' object=$conversation link='pageNo=%d'}
								<ul class="statusIcons">
									{if $conversation->isClosed}<li><img src="{icon size='S'}lock{/icon}" alt="" title="{lang}wcf.conversation.closed{/lang}" class="jsIconLock jsTooltip icon16" /></li>{/if}
									{if $conversation->attachments}<li><img src="{icon size='S'}attachment{/icon}" alt="" title="{lang}wcf.conversation.attachments{/lang}" class="jsIconAttachment jsTooltip icon16" /></li>{/if}
								</ul>
							</aside>
							
							<small>
								<a href="{link controller='User' object=$conversation->getUserProfile()->getDecoratedObject()}{/link}" class="userLink" data-user-id="{@$conversation->userID}">{$conversation->username}</a>
								- {@$conversation->time|time}
							</small>
							
							{if $conversation->getParticipantSummary()|count}
								<small>
									{lang}wcf.conversation.participants{/lang}: {implode from=$conversation->getParticipantSummary() item=participant}<a href="{link controller='User' object=$participant}{/link}" class="userLink" data-user-id="{@$participant->userID}"{if $participant->hideConversation == 2} style="text-decoration: line-through"{/if}>{$participant->username}</a>{/implode}
									{if $conversation->getParticipantSummary()|count < $conversation->participants - 1}{lang}wcf.conversation.participants.other{/lang}{/if}
								</small>
							{/if}
						</td>
						<td class="columnDigits columnReplies"><p>{#$conversation->replies}</p></td>
						<td class="columnDigits columnParticipants"><p>{#$conversation->participants}</p></td>
						<td class="columnText columnLastPost">
							{if $conversation->replies != 0}
								<div class="box24 wbbLastPost">
									<a href="{link controller='Conversation' object=$conversation}action=lastPost{/link}" class="framed jsTooltip" title="{lang}wcf.conversation.gotoLastPost{/lang}">{@$conversation->getLastPosterProfile()->getAvatar()->getImageTag(24)}</a>
									
									<hgroup>
										<h1>
											<a href="{link controller='User' object=$conversation->getLastPosterProfile()->getDecoratedObject()}{/link}" class="userLink" data-user-id="{@$conversation->getLastPosterProfile()->userID}">{$conversation->lastPoster}</a>
										</h1>
										<h2>{@$conversation->lastPostTime|time}</h2>
									</hgroup>
								</div>
							{/if}
						</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
{/if}

<div class="contentNavigation">
	{@$pagesLinks}
	
	{hascontent}
		<nav>
			<ul>
				{content}
					<li><a href="{link controller='ConversationAdd'}{/link}" title="{lang}wcf.conversation.add{/lang}" class="button"><img src="{icon size='M'}asterisk{/icon}" alt="" class="icon24" /> <span>{lang}wcf.conversation.button.add{/lang}</span></a></li>
					{event name='largeButtonsBottom'}
				{/content}
			</ul>
		</nav>
	{/hascontent}
</div>

{include file='footer' sandbox=false}

</body>
</html>