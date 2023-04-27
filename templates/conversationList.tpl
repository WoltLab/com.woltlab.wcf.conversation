{capture assign='pageTitle'}{if $filter}{lang}wcf.conversation.folder.{$filter}{/lang}{else}{$__wcf->getActivePage()->getTitle()}{/if}{if $pageNo > 1} - {lang}wcf.page.pageNo{/lang}{/if}{/capture}

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

{capture assign='headContent'}
	<link rel="alternate" type="application/rss+xml" title="{lang}wcf.global.button.rss{/lang}" href="{link controller='ConversationFeed'}at={@$__wcf->getUser()->userID}-{@$__wcf->getUser()->accessToken}{/link}">
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
	
	<section class="box">
		<h2 class="boxTitle">{lang}wcf.conversation.filter.participants{/lang}</h2>
		
		<div class="boxContent">
			<form action="{link controller='ConversationList'}{if $filter}filter={@$filter}&{/if}sortField={$sortField}&sortOrder={$sortOrder}&pageNo={@$pageNo}{/link}" method="post">
				<dl>
					<dt></dt>
					<dd><label><textarea id="participants" name="participants" class="long">{implode from=$participants item=participant glue=','}{$participant}{/implode}</textarea></label></dd>
				</dl>
				
				<div class="formSubmit">
					<input type="submit" value="{lang}wcf.global.button.submit{/lang}" accesskey="s">
					{csrfToken}
				</div>
			</form>
		</div>
	</section>
	
	<section class="box jsOnly">
		<h2 class="boxTitle">{lang}wcf.conversation.label{/lang}</h2>
		
		<div class="boxContent">
			<div id="conversationLabelFilter" class="dropdown">
				<div class="dropdownToggle" data-toggle="conversationLabelFilter">
					{if $labelID}
						{foreach from=$labelList item=label}
							{if $label->labelID == $labelID}
								<span class="badge label{if $label->cssClassName} {$label->cssClassName}{/if}">{$label->label}</span>
							{/if}
						{/foreach}
					{else}
						<span class="badge">{lang}wcf.conversation.label.filter{/lang}</span>
					{/if}
				</div>
				
				<div class="dropdownMenu">
					<ul class="scrollableDropdownMenu">
						{foreach from=$labelList item=label}
							<li><a href="{link controller='ConversationList'}{if $filter}filter={@$filter}&{/if}{if !$participants|empty}participants={implode from=$participants item=participant}{$participant|rawurlencode}{/implode}&{/if}sortField={$sortField}&sortOrder={$sortOrder}&pageNo={@$pageNo}&labelID={@$label->labelID}{/link}"><span class="badge label{if $label->cssClassName} {@$label->cssClassName}{/if}" data-css-class-name="{if $label->cssClassName}{@$label->cssClassName}{/if}" data-label-id="{$label->labelID}">{$label->label}</span></a></li>
						{/foreach}
					</ul>
					<ul>
						<li class="dropdownDivider"{if !$labelList|count} style="display: none;"{/if}></li>
						<li><a href="{link controller='ConversationList'}{if $filter}filter={@$filter}&{/if}{if !$participants|empty}participants={implode from=$participants item=participant}{$participant|rawurlencode}{/implode}&{/if}sortField={$sortField}&sortOrder={$sortOrder}&pageNo={@$pageNo}{/link}"><span class="badge label">{lang}wcf.conversation.label.disableFilter{/lang}</span></a></li>
					</ul>
				</div>
			</div>
		</div>
		
		<div class="boxContent">
			<button type="button" id="manageLabel" class="button">{lang}wcf.conversation.label.management{/lang}</button>
		</div>
	</section>
	
	{event name='beforeQuotaBox'}
	
	<section class="box conversationQuota">
		<h2 class="boxTitle">{lang}wcf.conversation.quota{/lang}</h2>
		
		<div class="boxContent">
			{assign var='conversationCount' value=$__wcf->getConversationHandler()->getConversationCount()}
			{assign var='maxConversationCount' value=$__wcf->session->getPermission('user.conversation.maxConversations')}
			<p class="conversationUsageBar{if $conversationCount/$maxConversationCount >= 1.0} red{elseif $conversationCount/$maxConversationCount > 0.9} yellow{/if}">
				<span style="width: {if $conversationCount/$maxConversationCount < 1.0}{$conversationCount/$maxConversationCount*100|round:0}{else}100{/if}%">{#$conversationCount/$maxConversationCount*100}%</span>
			</p>
			<p><small>{lang}wcf.conversation.quota.description{/lang}</small></p>
		</div>
	</section>
	
	{event name='boxes'}
{/capture}

{capture assign='contentInteractionPagination'}
	{assign var='participantsParameter' value=''}
	{if $participants}{capture assign='participantsParameter'}&participants={implode from=$participants item=participant}{$participant|rawurlencode}{/implode}{/capture}{/if}
	{assign var='labelIDParameter' value=''}
	{if $labelID}{assign var='labelIDParameter' value="&labelID=$labelID"}{/if}
	{pages print=true assign=pagesLinks controller='ConversationList' link="filter=$filter$participantsParameter&pageNo=%d&sortField=$sortField&sortOrder=$sortOrder$labelIDParameter"}
{/capture}

{capture assign='contentInteractionButtons'}
	<button type="button" class="markAllAsReadButton contentInteractionButton button small jsOnly">
		{icon name='check'}
		<span>{lang}wcf.global.button.markAllAsRead{/lang}</span>
	</button>
{/capture}

{capture assign='contentInteractionDropdownItems'}
	<li><a rel="alternate" href="{link controller='ConversationFeed'}at={@$__wcf->getUser()->userID}-{@$__wcf->getUser()->accessToken}{/link}">{lang}wcf.global.button.rss{/lang}</a></li>
{/capture}

{include file='header'}

{if !$items}
	<p class="info" role="status">{lang}wcf.conversation.noConversations{/lang}</p>
{else}
	<div class="section tabularBox messageGroupList conversationList jsClipboardContainer" data-type="com.woltlab.wcf.conversation.conversation">
		<ol class="tabularList">
			<li class="tabularListRow tabularListRowHead">
				<ol class="tabularListColumns">
					<li class="columnMark jsOnly"><label><input type="checkbox" class="jsClipboardMarkAll"></label></li>
					
					<li class="columnSort">
						<ul class="inlineList">
							<li>
								<a rel="nofollow" href="{link controller='ConversationList'}{if $filter}filter={@$filter}&{/if}{if !$participants|empty}participants={implode from=$participants item=participant}{$participant|rawurlencode}{/implode}&{/if}pageNo={@$pageNo}&sortField={$sortField}&sortOrder={if $sortOrder == 'ASC'}DESC{else}ASC{/if}{if $labelID}&labelID={@$labelID}{/if}{/link}">
									{if $sortOrder === 'ASC'}
										<span class="jsTooltip" title="{lang}wcf.global.sorting{/lang} ({lang}wcf.global.sortOrder.ascending{/lang})">
											{icon name='arrow-down-wide-short'}
										</span>
									{else}
										<span class="jsTooltip" title="{lang}wcf.global.sorting{/lang} ({lang}wcf.global.sortOrder.descending{/lang})">
											{icon name='arrow-down-short-wide'}
										</span>
									{/if}
								</a>
							</li>
							<li>
								<div class="dropdown">
									<span class="dropdownToggle">{if $sortField == 'subject'}{lang}wcf.global.subject{/lang}{else}{lang}wcf.conversation.{$sortField}{/lang}{/if}</span>
									
									<ul class="dropdownMenu">
										{foreach from=$validSortFields item=_sortField}
											<li{if $_sortField === $sortField} class="active"{/if}><a rel="nofollow" href="{link controller='ConversationList'}{if $filter}filter={@$filter}&{/if}{if !$participants|empty}participants={implode from=$participants item=participant}{$participant|rawurlencode}{/implode}&{/if}pageNo={@$pageNo}&sortField={$_sortField}&sortOrder={if $sortField === $_sortField}{if $sortOrder === 'DESC'}ASC{else}DESC{/if}{else}{$sortOrder}{/if}{if $labelID}&labelID={@$labelID}{/if}{/link}">{if $_sortField == 'subject'}{lang}wcf.global.subject{/lang}{else}{lang}wcf.conversation.{$_sortField}{/lang}{/if}</a></li>
										{/foreach}
									</ul>
								</div>
							</li>
						</ul>
					</li>
				</ol>
			</li>
			
			{foreach from=$objects item=conversation}
				<li class="tabularListRow">
					<ol class="tabularListColumns messageGroup conversation jsClipboardObject{if $conversation->isNew()} new{/if}" data-conversation-id="{$conversation->conversationID}" data-label-ids="[ {implode from=$conversation->getAssignedLabels() item=label}{$label->labelID}{/implode} ]" data-is-closed="{$conversation->isClosed}" data-can-close-conversation="{if $conversation->userID == $__wcf->getUser()->userID}1{else}0{/if}" data-can-add-participants="{if $conversation->canAddParticipants()}1{else}0{/if}">
						<li class="columnMark jsOnly">
							<label><input type="checkbox" class="jsClipboardItem" data-object-id="{$conversation->conversationID}"></label>
						</li>
						<li class="columnIcon columnAvatar">
							{if $conversation->getUserProfile()->getAvatar()}
								<div>
									<p{if $conversation->isNew()} title="{lang}wcf.conversation.markAsRead.doubleClick{/lang}"{/if}>{@$conversation->getUserProfile()->getAvatar()->getImageTag(48)}</p>
									
									{if $conversation->ownPosts && $conversation->userID != $__wcf->user->userID}
										{if $__wcf->getUserProfileHandler()->getAvatar()}
											<small class="myAvatar jsTooltip" title="{lang}wcf.conversation.ownPosts{/lang}">{@$__wcf->getUserProfileHandler()->getAvatar()->getImageTag(24)}</small>
										{/if}
									{/if}
								</div>
							{/if}
						</li>
						<li class="columnSubject">
							{hascontent}
								<ul class="labelList">
									{content}
										{foreach from=$conversation->getAssignedLabels() item=label}
											<li><a href="{link controller='ConversationList'}{if $filter}filter={@$filter}&{/if}{if !$participants|empty}participants={implode from=$participants item=participant}{$participant|rawurlencode}{/implode}&{/if}sortField={$sortField}&sortOrder={$sortOrder}&pageNo={@$pageNo}&labelID={@$label->labelID}{/link}" class="badge label{if $label->cssClassName} {@$label->cssClassName}{/if}">{$label->label}</a></li>
										{/foreach}
									{/content}
								</ul>
							{/hascontent}
								
							<h3>
								<a href="{if $conversation->isNew()}{link controller='Conversation' object=$conversation}action=firstNew{/link}{else}{$conversation->getLink()}{/if}" class="conversationLink messageGroupLink" data-object-id="{$conversation->conversationID}">{$conversation->subject}</a>
								{if $conversation->replies}
									<span class="badge messageGroupCounterMobile">{@$conversation->replies|shortUnit}</span>
								{/if}
							</h3>
							
							<aside class="statusDisplay" role="presentation">
								<ul class="statusIcons">
									{if $conversation->isClosed}
										<li>
											<span class="jsIconLock jsTooltip" title="{lang}wcf.global.state.closed{/lang}">
												{icon name='lock'}
											</span>
										</li>
									{/if}
									{if $conversation->attachments}
										<li>
											<span class="jsIconAttachment jsTooltip" title="{lang}wcf.conversation.attachments{/lang}">
												{icon name='paperclip'}
											</span>
										</li>
									{/if}
								</ul>
							</aside>
							
							<ul class="inlineList dotSeparated small messageGroupInfo">
								<li class="messageGroupAuthor">{user object=$conversation->getUserProfile()}</li>
								<li class="messageGroupTime">{time time=$conversation->time}</li>
								<li class="messageGroupEditLink jsOnly"><a href="#" class="jsConversationInlineEditor">{lang}wcf.global.button.edit{/lang}</a></li>
								{event name='messageGroupInfo'}
							</ul>
							
							<ul class="messageGroupInfoMobile">
								<li class="messageGroupAuthorMobile">{$conversation->username}</li>
								<li class="messageGroupLastPostTimeMobile">{time time=$conversation->lastPostTime}</li>
							</ul>
							
							{if $conversation->getParticipantSummary()|count}
								<small class="conversationParticipantSummary">
									{assign var='participantSummaryCount' value=$conversation->getParticipantSummary()|count}
									{lang}wcf.conversation.participants{/lang}: {implode from=$conversation->getParticipantSummary() item=participant}<a href="{$participant->getLink()}" class="userLink{if $participant->hideConversation == 2} conversationLeft{/if}" data-object-id="{$participant->userID}">{$participant->username}</a>{/implode}
									{if $participantSummaryCount < $conversation->participants}{lang}wcf.conversation.participants.other{/lang}{/if}
								</small>
							{/if}
							
							{event name='conversationData'}
						</li>
						<li class="columnStats">
							<dl class="plain statsDataList">
								<dt>{lang}wcf.conversation.replies{/lang}</dt>
								<dd>{@$conversation->replies|shortUnit}</dd>
							</dl>
							<dl class="plain statsDataList">
								<dt>{lang}wcf.conversation.participants{/lang}</dt>
								<dd>{@$conversation->participants|shortUnit}</dd>
							</dl>
							
							<div class="messageGroupListStatsSimple">
								{if $conversation->replies}
									<span aria-label="{lang}wcf.conversation.replies{/lang}">
										{icon name='comment'}
									</span>
									{@$conversation->replies|shortUnit}
								{/if}
							</div>
						</li>
						<li class="columnLastPost">
							{if $conversation->replies != 0 && $conversation->lastPostTime}
								<div class="box32">
									<a href="{link controller='Conversation' object=$conversation}action=lastPost{/link}" class="jsTooltip" title="{lang}wcf.conversation.gotoLastPost{/lang}">{@$conversation->getLastPosterProfile()->getAvatar()->getImageTag(32)}</a>
									
									<div>
										<p>
											{user object=$conversation->getLastPosterProfile()}
										</p>
										<small>{time time=$conversation->lastPostTime}</small>
									</div>
								</div>
							{/if}
						</li>
						
						{event name='columns'}
					</ol>
				</li>
			{/foreach}
		</ol>
	</div>
{/if}

<footer class="contentFooter">
	{hascontent}
		<div class="paginationBottom">
			{content}{@$pagesLinks}{/content}
		</div>
	{/hascontent}
	
	{hascontent}
		<nav class="contentFooterNavigation">
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
					{event name='contentFooterNavigation'}
				{/content}
			</ul>
		</nav>
	{/hascontent}
</footer>

<script data-relocate="true" src="{$__wcf->getPath()}js/WCF.Conversation{if !ENABLE_DEBUG_MODE}.min{/if}.js?v={@LAST_UPDATE_TIME}"></script>
<script data-relocate="true">
	require([
		'WoltLabSuite/Core/Language',
		'WoltLabSuite/Core/Controller/Popover',
		'WoltLabSuite/Core/Ui/ItemList/User',
		'WoltLabSuite/Core/Controller/Clipboard',
	], (
		Language,
		ControllerPopover,
		UiItemListUser,
		ControllerClipboard
	) => {
		Language.addObject({
			'wcf.conversation.edit.addParticipants': '{jslang}wcf.conversation.edit.addParticipants{/jslang}',
			'wcf.conversation.edit.assignLabel': '{jslang}wcf.conversation.edit.assignLabel{/jslang}',
			'wcf.conversation.edit.close': '{jslang}wcf.conversation.edit.close{/jslang}',
			'wcf.conversation.edit.leave': '{jslang}wcf.conversation.edit.leave{/jslang}',
			'wcf.conversation.edit.open': '{jslang}wcf.conversation.edit.open{/jslang}',
			'wcf.conversation.edit.subject': '{jslang}wcf.conversation.edit.subject{/jslang}',
			'wcf.conversation.label.management': '{jslang}wcf.conversation.label.management{/jslang}',
			'wcf.conversation.label.management.addLabel.success': '{jslang}wcf.conversation.label.management.addLabel.success{/jslang}',
			'wcf.conversation.label.management.deleteLabel.confirmMessage': '{jslang}wcf.conversation.label.management.deleteLabel.confirmMessage{/jslang}',
			'wcf.conversation.label.management.editLabel': '{jslang}wcf.conversation.label.management.editLabel{/jslang}',
			'wcf.conversation.label.placeholder': '{jslang}wcf.conversation.label.placeholder{/jslang}',
			'wcf.conversation.leave.title': '{jslang}wcf.conversation.leave.title{/jslang}',
			'wcf.global.state.closed': '{jslang}wcf.global.state.closed{/jslang}',
			'wcf.global.subject': '{jslang}wcf.global.subject{/jslang}',
			'wcf.conversation.label.assignLabels': '{jslang}wcf.conversation.label.assignLabels{/jslang}'
		});
		
		ControllerClipboard.setup({
			pageClassName: 'wcf\\page\\ConversationListPage',
			hasMarkedItems: {if $hasMarkedItems}true{else}false{/if},
		});
		
		var $editorHandler = new WCF.Conversation.EditorHandler();
		var $inlineEditor = new WCF.Conversation.InlineEditor('.conversation');
		$inlineEditor.setEditorHandler($editorHandler, 'list');
		
		new WCF.Conversation.Clipboard($editorHandler);
		new WCF.Conversation.Label.Manager('{link controller='ConversationList' encode=false}{if $filter}filter={@$filter}&{/if}{if !$participants|empty}participants={implode from=$participants item=participant}{$participant|rawurlencode}{/implode}&{/if}sortField={$sortField}&sortOrder={$sortOrder}&pageNo={@$pageNo}{/link}');
		
		// mobile safari hover workaround
		if ($(window).width() <= 800) {
			$('.sidebar').addClass('mobileSidebar').hover(function() { });
		}
		
		UiItemListUser.init('participants', {
			excludedSearchValues: ['{$__wcf->user->username|encodeJS}'],
			maxItems: 20
		});
		
		ControllerPopover.init({
			className: 'conversationLink',
			dboAction: 'wcf\\data\\conversation\\ConversationAction',
			identifier: 'com.woltlab.wcf.conversation'
		});
	});
</script>

<script data-relocate="true">
	require(['WoltLabSuite/Core/Conversation/Ui/MarkAsRead'], (MarkAsRead) => {
		MarkAsRead.setup();
	});
	require(['WoltLabSuite/Core/Conversation/Ui/MarkAllAsRead'], (MarkAllAsRead) => {
		MarkAllAsRead.setup();
	});
</script>

{include file='footer'}
