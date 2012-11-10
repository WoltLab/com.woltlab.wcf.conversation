{include file='documentHeader'}

<head>
	<title>{lang}wcf.conversation.conversations{/lang} {if $pageNo > 1}- {lang}wcf.page.pageNo{/lang} {/if}- {PAGE_TITLE|language}</title>
	
	{include file='headInclude'}
	
	<link rel="alternate" type="application/rss+xml" title="{lang}wcf.global.button.rss{/lang}" href="{link controller='ConversationFeed' appendSession=false}at={@$__wcf->getUser()->userID}-{@$__wcf->getUser()->accessToken}{/link}" />
	<script type="text/javascript" src="{@$__wcf->getPath()}js/WCF.Conversation.js"></script>
	<script type="text/javascript">
		//<![CDATA[
		$(function() {
			WCF.Language.addObject({
				'wcf.conversation.edit.addParticipants': '{lang}wcf.conversation.edit.addParticipants{/lang}',
				'wcf.conversation.edit.assignLabel': '{lang}wcf.conversation.edit.assignLabel{/lang}',
				'wcf.conversation.edit.close': '{lang}wcf.conversation.edit.close{/lang}',
				'wcf.conversation.edit.leave': '{lang}wcf.conversation.edit.leave{/lang}',
				'wcf.conversation.edit.open': '{lang}wcf.conversation.edit.open{/lang}',
				'wcf.conversation.label.management': '{lang}wcf.conversation.label.management{/lang}',
				'wcf.conversation.label.management.addLabel.success': '{lang}wcf.conversation.label.management.addLabel.success{/lang}',
				'wcf.conversation.label.management.deleteLabel.confirmMessage': '{lang}wcf.conversation.label.management.deleteLabel.confirmMessage{/lang}',
				'wcf.conversation.label.management.editLabel': '{lang}wcf.conversation.label.management.editLabel{/lang}',
				'wcf.conversation.label.placeholder': '{lang}wcf.conversation.label.placeholder{/lang}',
				'wcf.conversation.leave.title': '{lang}wcf.conversation.leave.title{/lang}',
				'wcf.global.state.closed': '{lang}wcf.global.state.closed{/lang}'
			});
			WCF.Icon.addObject({
				'wcf.icon.lock': '{icon}lock{/icon}'
			});
			
			WCF.Clipboard.init('wcf\\page\\ConversationListPage', {@$hasMarkedItems}, { });
			
			var $editorHandler = new WCF.Conversation.EditorHandler();
			var $inlineEditor = new WCF.Conversation.InlineEditor('.conversation');
			$inlineEditor.setEditorHandler($editorHandler, 'list');
			
			new WCF.Conversation.Clipboard($editorHandler);
			new WCF.Conversation.Label.Manager('{link controller='ConversationList'}{if $filter}filter={@$filter}{/if}&sortField={$sortField}&sortOrder={$sortOrder}&pageNo={@$pageNo}{/link}');

			new WCF.Conversation.Preview();
		});
		//]]>
	</script>
</head>

<body id="tpl{$templateName|ucfirst}">

{capture assign='sidebar'}
	<fieldset>
		<legend>{lang}wcf.conversation.folders{/lang}</legend>
		
		<nav>
			<ul>
				<li{if $filter == ''} class="active"{/if}><a href="{link controller='ConversationList'}{/link}">{lang}wcf.conversation.conversations{/lang}</a></li>
				<li{if $filter == 'draft'} class="active"{/if}><a href="{link controller='ConversationList'}filter=draft{/link}">{lang}wcf.conversation.folder.draft{/lang}</a></li>
				<li{if $filter == 'outbox'} class="active"{/if}><a href="{link controller='ConversationList'}filter=outbox{/link}">{lang}wcf.conversation.folder.outbox{/lang}</a></li>
				<li{if $filter == 'hidden'} class="active"{/if}><a href="{link controller='ConversationList'}filter=hidden{/link}">{lang}wcf.conversation.folder.hidden{/lang}</a></li>
			</ul>
		</nav>
	</fieldset>
	
	<fieldset>
		<legend>{lang}wcf.conversation.label{/lang}</legend>
		
		<div id="conversationLabelFilter" class="dropdown">
			<div class="dropdownToggle" data-toggle="conversationLabelFilter">
				{if $labelID}
					{foreach from=$labelList item=label}
						{if $label->labelID == $labelID}
							<span class="badge label{if $label->cssClassName} {@$label->cssClassName}{/if}">{$label->label}</span>
						{/if}
					{/foreach}
				{else}
					<span class="badge">{lang}wcf.conversation.label.filter{/lang}</span>
				{/if}
			</div>
			
			<ul class="dropdownMenu">
				{foreach from=$labelList item=label}
					<li><a href="{link controller='ConversationList'}{if $filter}filter={@$filter}{/if}&sortField={$sortField}&sortOrder={$sortOrder}&pageNo={@$pageNo}&labelID={@$label->labelID}{/link}"><span class="badge label{if $label->cssClassName} {@$label->cssClassName}{/if}" data-css-class-name="{if $label->cssClassName}{@$label->cssClassName}{/if}" data-label-id="{@$label->labelID}">{$label->label}</span></a></li>
				{/foreach}
				<li class="dropdownDivider"{if !$labelList|count} style="display: none;"{/if}></li>
				<li><a href="{link controller='ConversationList'}{if $filter}filter={@$filter}{/if}&sortField={$sortField}&sortOrder={$sortOrder}&pageNo={@$pageNo}{/link}">{lang}wcf.conversation.label.disableFilter{/lang}</a></li>
			</ul>
		</div>
		
		<button id="manageLabel">{lang}wcf.conversation.label.management{/lang}</button>
	</fieldset>
	
	<fieldset class="conversationQuota">
		<legend>{lang}wcf.conversation.quota{/lang}</legend>
		
		<div>
			{assign var='conversationCount' value=$__wcf->getConversationHandler()->getConversationCount()}
			{assign var='maxConversationCount' value=$__wcf->session->getPermission('user.conversation.maxConversations')}
			<p class="conversationUsageBar{if $conversationCount/$maxConversationCount > 0.9} yellow{elseif $conversationCount/$maxConversationCount >= 1.0} red{/if}">
				<span style="width: {if $conversationCount/$maxConversationCount < 1.0}{@$conversationCount/$maxConversationCount*100|round:0}{else}100{/if}%">{#$conversationCount/$maxConversationCount*100}%</span>
			</p>
			<p><small>{lang}wcf.conversation.quota.description{/lang}</small></p>
		</div>
	</fieldset>
{/capture}

{capture assign='headerNavigation'}
	<li><a href="{link controller='ConversationFeed' appendSession=false}at={@$__wcf->getUser()->userID}-{@$__wcf->getUser()->accessToken}{/link}" title="{lang}wcf.global.button.rss{/lang}" class="jsTooltip"><img src="{icon}rssColored{/icon}" class="icon16" alt="" /> <span class="invisible">{lang}wcf.global.button.rss{/lang}</span></a></li>
{/capture}

{include file='header' sidebarOrientation='left'}

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
					<li><a href="{link controller='ConversationAdd'}{/link}" title="{lang}wcf.conversation.add{/lang}" class="button"><img src="{icon}asterisk{/icon}" alt="" class="icon24" /> <span>{lang}wcf.conversation.button.add{/lang}</span></a></li>
					{event name='largeButtonsTop'}
				{/content}
			</ul>
		</nav>
	{/hascontent}
</div>

{if !$items}
	<p class="info">{lang}wcf.conversation.noConversations{/lang}</p>
{else}
	<div class="marginTop tabularBox tabularBoxTitle shadow messageGroupList jsClipboardContainer" data-type="com.woltlab.wcf.conversation.conversation"> {*todo: use generic css class*}
		<hgroup>
			<h1>{lang}wcf.conversation.conversations{/lang} <span class="badge badgeInverse">{#$items}</span></h1>
		</hgroup>
		
		<table class="table">
			<thead>
				<tr>
					<th class="columnMark"><label><input type="checkbox" class="jsClipboardMarkAll" /></label></th>
					<th colspan="2" class="columnTitle columnSubject{if $sortField == 'subject'} active{/if}"><a href="{link controller='ConversationList'}{if $filter}filter={@$filter}&{/if}pageNo={@$pageNo}&sortField=subject&sortOrder={if $sortField == 'subject' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{if $labelID}&labelID={@$labelID}{/if}{/link}">{lang}wcf.global.subject{/lang}{if $sortField == 'subject'} <img src="{icon}sort{@$sortOrder}{/icon}" alt="" />{/if}</a></th>
					<th class="columnDigits columnReplies{if $sortField == 'replies'} active{/if}"><a href="{link controller='ConversationList'}{if $filter}filter={@$filter}&{/if}pageNo={@$pageNo}&sortField=replies&sortOrder={if $sortField == 'replies' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{if $labelID}&labelID={@$labelID}{/if}{/link}">{lang}wcf.conversation.replies{/lang}{if $sortField == 'replies'} <img src="{icon}sort{@$sortOrder}{/icon}" alt="" />{/if}</a></th>
					<th class="columnDigits columnParticipants{if $sortField == 'participants'} active{/if}"><a href="{link controller='ConversationList'}{if $filter}filter={@$filter}&{/if}pageNo={@$pageNo}&sortField=participants&sortOrder={if $sortField == 'participants' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{if $labelID}&labelID={@$labelID}{/if}{/link}">{lang}wcf.conversation.participants{/lang}{if $sortField == 'participants'} <img src="{icon}sort{@$sortOrder}{/icon}" alt="" />{/if}</a></th>
					<th class="columnText columnLastPost{if $sortField == 'lastPostTime'} active{/if}"><a href="{link controller='ConversationList'}{if $filter}filter={@$filter}&{/if}pageNo={@$pageNo}&sortField=lastPostTime&sortOrder={if $sortField == 'lastPostTime' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{if $labelID}&labelID={@$labelID}{/if}{/link}">{lang}wcf.conversation.lastPostTime{/lang}{if $sortField == 'lastPostTime'} <img src="{icon}sort{@$sortOrder}{/icon}" alt="" />{/if}</a></th>
				</tr>
			</thead>
			
			<tbody>
				{foreach from=$objects item=conversation}
					<tr class="conversation{if $conversation->isNew()} new{/if}" data-conversation-id="{@$conversation->conversationID}" data-label-ids="[ {implode from=$conversation->getAssignedLabels() item=label}{@$label->labelID}{/implode} ]" data-is-closed="{@$conversation->isClosed}" data-can-close-conversation="{if $conversation->userID == $__wcf->getUser()->userID}1{else}0{/if}" data-can-add-participants="{if $conversation->canAddParticipants()}1{else}0{/if}">
						<td class="columnMark">
							<label><input type="checkbox" class="jsClipboardItem" data-object-id="{@$conversation->conversationID}" /></label>
						</td>
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
						<td class="columnText columnSubject">
							<h1>
								{hascontent}
									<ul class="labelList">
										{content}
											{foreach from=$conversation->getAssignedLabels() item=label}
												<li><a href="{link controller='ConversationList'}{if $filter}filter={@$filter}{/if}&sortField={$sortField}&sortOrder={$sortOrder}&pageNo={@$pageNo}&labelID={@$label->labelID}{/link}" class="badge label{if $label->cssClassName} {@$label->cssClassName}{/if}">{$label->label}</a></li>
											{/foreach}
										{/content}
									</ul>
								{/hascontent}
								
								{if $conversation->isNew()}
									<a href="{link controller='Conversation' object=$conversation}action=firstNew{/link}" class="jsTooltip" title="{lang}wcf.conversation.gotoFirstNewPost{/lang}"><img src="{icon}circleArrowDown{/icon}" alt="" class="firstNewPost icon16" /></a>
								{/if}
								
								<a href="{link controller='Conversation' object=$conversation}{/link}" class="conversationLink messageGroupLink" data-conversation-id="{@$conversation->conversationID}">{$conversation->subject}</a>
							</h1>
							
							<aside class="statusDisplay">
								{smallpages pages=$conversation->getPages() controller='Conversation' object=$conversation link='pageNo=%d'}
								<ul class="statusIcons">
									{if $conversation->isClosed}<li><img src="{icon}lock{/icon}" alt="" title="{lang}wcf.global.state.closed{/lang}" class="jsIconLock jsTooltip icon16" /></li>{/if}
									{if $conversation->attachments}<li><img src="{icon}attachment{/icon}" alt="" title="{lang}wcf.conversation.attachments{/lang}" class="jsIconAttachment jsTooltip icon16" /></li>{/if}
								</ul>
							</aside>
							
							<small>
								<a href="{link controller='User' object=$conversation->getUserProfile()->getDecoratedObject()}{/link}" class="userLink" data-user-id="{@$conversation->userID}">{$conversation->username}</a>
								- {@$conversation->time|time}
								- <a class="jsThreadInlineEditor">{lang}wcf.global.button.edit{/lang}</a>
							</small>
							
							{if $conversation->getParticipantSummary()|count}
								<small>
									{lang}wcf.conversation.participants{/lang}: {implode from=$conversation->getParticipantSummary() item=participant}<a href="{link controller='User' object=$participant}{/link}" class="userLink{if $participant->hideConversation == 2} conversationLeft{/if}" data-user-id="{@$participant->userID}">{$participant->username}</a>{/implode}
									{if $conversation->getParticipantSummary()|count < $conversation->participants - 1}{lang}wcf.conversation.participants.other{/lang}{/if}
								</small>
							{/if}
						</td>
						<td class="columnDigits columnReplies"><p>{#$conversation->replies}</p></td>
						<td class="columnDigits columnParticipants"><p>{#$conversation->participants}</p></td>
						<td class="columnText columnLastPost">
							{if $conversation->replies != 0}
								<div class="box24">
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
	
	<div class="jsClipboardEditor" data-types="[ 'com.woltlab.wcf.conversation.conversation' ]"></div>
	
	{hascontent}
		<nav>
			<ul>
				{content}
					<li><a href="{link controller='ConversationAdd'}{/link}" title="{lang}wcf.conversation.add{/lang}" class="button"><img src="{icon}asterisk{/icon}" alt="" class="icon24" /> <span>{lang}wcf.conversation.button.add{/lang}</span></a></li>
					{event name='largeButtonsBottom'}
				{/content}
			</ul>
		</nav>
	{/hascontent}
</div>

{include file='footer'}

</body>
</html>