{capture assign='pageTitle'}{$conversation->subject} {if $pageNo > 1} - {lang}wcf.page.pageNo{/lang}{/if}{/capture}

{capture assign='contentHeader'}
	<header class="contentHeader">
		<div class="contentHeaderIcon">
			{@$conversation->getUserProfile()->getAvatar()->getImageTag(64)}
		</div>
		
		<div class="contentHeaderTitle">
			<h1 class="contentTitle">{$conversation->subject}</h1>
			
			<ul class="inlineList contentHeaderMetaData">
				{hascontent}
					<li>
						<span class="icon icon16 fa-tags"></span>
						<ul class="labelList">
							{content}
								{foreach from=$conversation->getAssignedLabels() item=label}
									<li><span class="label badge{if $label->cssClassName} {$label->cssClassName}{/if}">{lang}{$label->label}{/lang}</span></li>
								{/foreach}
							{/content}
						</ul>
					</li>
				{/hascontent}
				
				<li>
					<span class="icon icon16 fa-user"></span>
					{if $conversation->userID}
						<a href="{link controller='User' object=$conversation->getUserProfile()->getDecoratedObject()}{/link}" class="userLink" data-user-id="{@$conversation->userID}">{$conversation->username}</a>
					{else}
						{$conversation->username}
					{/if}
				</li>
				
				<li>
					<span class="icon icon16 fa-clock-o"></span>
					{@$conversation->time|time}
				</li>
				
				{if $conversation->isClosed}
					<li>
						<span class="icon icon16 fa-lock jsIconLock"></span>
						{lang}wcf.global.state.closed{/lang}
					</li>
				{/if}
			</ul>
		</div>
		
		<nav class="contentHeaderNavigation">
			<ul class="conversation jsConversationInlineEditorContainer" data-conversation-id="{@$conversation->conversationID}" data-label-ids="[ {implode from=$conversation->getAssignedLabels() item=label}{@$label->labelID}{/implode} ]" data-is-closed="{@$conversation->isClosed}" data-can-close-conversation="{if $conversation->userID == $__wcf->getUser()->userID}1{else}0{/if}" data-can-add-participants="{if $conversation->canAddParticipants()}1{else}0{/if}" data-is-draft="{if $conversation->isDraft}1{else}0{/if}">
				<li class="jsOnly"><a href="{if $conversation->isDraft}{link controller='ConversationDraftEdit' id=$conversation->conversationID}{/link}{else}#{/if}" class="button jsConversationInlineEditor"><span class="icon icon16 fa-pencil"></span> <span>{lang}wcf.global.button.edit{/lang}</span></a></li>
				{if !$conversation->isClosed}<li class="jsOnly"><a href="#" title="{lang}wcf.conversation.message.add{/lang}" class="button buttonPrimary jsQuickReply"><span class="icon icon16 fa-plus"></span> <span>{lang}wcf.conversation.message.button.add{/lang}</span></a></li>{/if}
				{event name='contentHeaderNavigation'}
			</ul>
		</nav>
	</header>
{/capture}

{include file='header'}

{if !$conversation->isDraft}
	<section class="section">
		<h2 class="sectionTitle">{lang}wcf.conversation.participants{/lang}</h2>
		
		<ul class="containerBoxList tripleColumned conversationParticipantList">
			{foreach from=$participants item=participant}
				<li class="jsParticipant{if !$participant->userID || $participant->hideConversation == 2} conversationLeft{/if}">
					<div class="box24">
						{if $participant->userID}<a href="{link controller='User' object=$participant}{/link}">{@$participant->getAvatar()->getImageTag(24)}</a>{else}<span>{@$participant->getAvatar()->getImageTag(24)}</span>{/if}
						<div>
							<p>
								{if $participant->userID}<a href="{link controller='User' object=$participant}{/link}" class="userLink" data-user-id="{@$participant->userID}">{$participant->username}</a>{else}<span>{$participant->username}</span>{/if}
								{if $participant->isInvisible}<small>({lang}wcf.conversation.invisible{/lang})</small>{/if}
								{if $participant->userID && ($conversation->userID == $__wcf->getUser()->userID) && ($participant->userID != $__wcf->getUser()->userID) && $participant->hideConversation != 2}
									<a href="#" class="jsDeleteButton jsTooltip jsOnly" title="{lang}wcf.conversation.participants.removeParticipant{/lang}" data-confirm-message-html="{lang __encode=true}wcf.conversation.participants.removeParticipant.confirmMessage{/lang}" data-object-id="{@$participant->userID}"><span class="icon icon16 fa-times"></span></a>
								{/if}
							</p>
							<dl class="plain inlineDataList small">
								<dt>{lang}wcf.conversation.lastVisitTime{/lang}</dt>
								<dd>{if $participant->lastVisitTime}{@$participant->lastVisitTime|time}{else}-{/if}</dd>
							</dl>
						</div>
					</div>
				</li>
			{/foreach}
		</ul>
	</section>
{/if}

{hascontent}
	<div class="paginationTop">
		{content}{pages print=true assign=pagesLinks controller='Conversation' object=$conversation link="pageNo=%d"}{/content}
	</div>
{/hascontent}

<div class="section">
	<ul class="messageList">
		{include file='conversationMessageList'}
		{hascontent}
			<li class="messageListPagination">
				{content}{@$pagesLinks}{/content}
			</li>
		{/hascontent}
		{if !$conversation->isClosed}{include file='conversationQuickReply'}{/if}
	</ul>
</div>

{if !ENABLE_DEBUG_MODE}<script src="{@$__wcf->getPath()}js/WoltLabSuite.Core.Conversation.min.js?v={@LAST_UPDATE_TIME}"></script>{/if}
<script data-relocate="true" src="{@$__wcf->getPath()}js/WCF.Conversation{if !ENABLE_DEBUG_MODE}.min{/if}.js?v={@LAST_UPDATE_TIME}"></script>
<script data-relocate="true">
	$(function() {
		WCF.Language.addObject({
			'wcf.conversation.edit.addParticipants': '{lang}wcf.conversation.edit.addParticipants{/lang}',
			'wcf.conversation.edit.assignLabel': '{lang}wcf.conversation.edit.assignLabel{/lang}',
			'wcf.conversation.edit.close': '{lang}wcf.conversation.edit.close{/lang}',
			'wcf.conversation.edit.leave': '{lang}wcf.conversation.edit.leave{/lang}',
			'wcf.conversation.edit.open': '{lang}wcf.conversation.edit.open{/lang}',
			'wcf.conversation.leave.title': '{lang}wcf.conversation.leave.title{/lang}',
			'wcf.global.state.closed': '{lang}wcf.global.state.closed{/lang}',
			'wcf.message.bbcode.code.copy': '{lang}wcf.message.bbcode.code.copy{/lang}',
			'wcf.message.error.editorAlreadyInUse': '{lang}wcf.message.error.editorAlreadyInUse{/lang}',
			'wcf.moderation.report.reportContent': '{lang}wcf.moderation.report.reportContent{/lang}',
			'wcf.moderation.report.success': '{lang}wcf.moderation.report.success{/lang}',
			'wcf.conversation.label.assignLabels': '{lang}wcf.conversation.label.assignLabels{/lang}'
		});
		
		var $availableLabels = [ {implode from=$labelList item=label}{ cssClassName: '{if $label->cssClassName}{@$label->cssClassName}{/if}', labelID: {@$label->labelID}, label: '{$label->label}' }{/implode} ];
		var $editorHandler = new WCF.Conversation.EditorHandlerConversation($availableLabels);
		var $inlineEditor = new WCF.Conversation.InlineEditor('.conversation');
		$inlineEditor.setEditorHandler($editorHandler);
		
		{assign var=__supportPaste value=true}
		{if $conversation->isClosed}{assign var=__supportPaste value=false}{/if}
		{include file='__messageQuoteManager' wysiwygSelector='text' supportPaste=$__supportPaste}
		
		new WCF.Conversation.Message.InlineEditor({@$conversation->conversationID}, $quoteManager);
		new WCF.Conversation.Message.QuoteHandler($quoteManager);
		{* if !$conversation->isClosed}new WCF.Conversation.QuickReply($quoteManager);{/if *}
		
		{if $__wcf->session->getPermission('user.profile.canReportContent')}
		new WCF.Moderation.Report.Content('com.woltlab.wcf.conversation.message', '.jsReportConversationMessage');
		{/if}
		new WCF.Conversation.RemoveParticipant({@$conversation->conversationID});
		new WCF.Message.BBCode.CodeViewer();
	});
</script>

{include file='footer'}
