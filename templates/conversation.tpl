{capture assign='pageTitle'}{$conversation->subject} {if $pageNo > 1} - {lang}wcf.page.pageNo{/lang}{/if}{/capture}

{assign var='__pageCssClassName' value='mobileShowPaginationTop'}

{capture assign='contentHeader'}
	<header class="contentHeader">
		<div class="contentHeaderIcon">
			{@$conversation->getUserProfile()->getAvatar()->getImageTag(64)}
		</div>
		
		<div class="contentHeaderTitle">
			<h1 class="contentTitle jsConversationSubject" data-conversation-id="{@$conversation->conversationID}">{$conversation->subject}</h1>
			
			<ul class="inlineList contentHeaderMetaData">
				{hascontent}
					<li>
						<span class="icon icon16 fa-tags"></span>
						<ul class="labelList">
							{content}
								{foreach from=$conversation->getAssignedLabels() item=label}
									<li><span class="label badge{if $label->cssClassName} {$label->cssClassName}{/if}">{$label->label}</span></li>
								{/foreach}
							{/content}
						</ul>
					</li>
				{/hascontent}
				
				<li>
					<span class="icon icon16 fa-user"></span>
					{user object=$conversation->getUserProfile()}
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
				{if $conversation->canReply()}<li class="jsOnly"><a href="#" class="button buttonPrimary jsQuickReply"><span class="icon icon16 fa-reply"></span> <span>{lang}wcf.conversation.message.button.add{/lang}</span></a></li>{/if}
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
				<li class="jsParticipant{if !$participant->userID || $participant->hideConversation == 2 || $participant->leftAt > 0} conversationLeft{/if}">
					<div class="box24">
						{user object=$participant type='avatar24'}
						<div>
							<p>
								{user object=$participant}
								{if $participant->isInvisible}<small>({lang}wcf.conversation.invisible{/lang})</small>{/if}
								{if $participant->userID && ($conversation->userID == $__wcf->getUser()->userID) && ($participant->userID != $__wcf->getUser()->userID) && $participant->hideConversation != 2 && $participant->leftAt == 0}
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
		{if $pageNo == 1 && !$conversation->joinedAt|empty}<li><p class="info" role="status">{lang}wcf.conversation.visibility.previousMessages{/lang}</p></li>{/if}
		{include file='conversationMessageList'}
		{hascontent}
			<li class="messageListPagination">
				{content}{@$pagesLinks}{/content}
			</li>
		{/hascontent}
		{if $conversation->canReply()}{include file='conversationQuickReply'}{/if}
		{if $pageNo == $pages && !$conversation->leftAt|empty}<li><p class="info" role="status">{lang}wcf.conversation.visibility.nextMessages{/lang}</p></li>{/if}
	</ul>
</div>

{if !ENABLE_DEBUG_MODE}<script src="{@$__wcf->getPath()}js/WoltLabSuite.Core.Conversation.min.js?v={@LAST_UPDATE_TIME}"></script>{/if}
<script data-relocate="true" src="{@$__wcf->getPath()}js/WCF.Conversation{if !ENABLE_DEBUG_MODE}.min{/if}.js?v={@LAST_UPDATE_TIME}"></script>
<script data-relocate="true">
	$(function() {
		WCF.Language.addObject({
			'wcf.conversation.edit.addParticipants': '{jslang}wcf.conversation.edit.addParticipants{/jslang}',
			'wcf.conversation.edit.assignLabel': '{jslang}wcf.conversation.edit.assignLabel{/jslang}',
			'wcf.conversation.edit.close': '{jslang}wcf.conversation.edit.close{/jslang}',
			'wcf.conversation.edit.leave': '{jslang}wcf.conversation.edit.leave{/jslang}',
			'wcf.conversation.edit.open': '{jslang}wcf.conversation.edit.open{/jslang}',
			'wcf.conversation.edit.subject': '{jslang}wcf.conversation.edit.subject{/jslang}',
			'wcf.conversation.leave.title': '{jslang}wcf.conversation.leave.title{/jslang}',
			'wcf.global.state.closed': '{jslang}wcf.global.state.closed{/jslang}',
			'wcf.global.subject': '{jslang}wcf.global.subject{/jslang}',
			'wcf.message.bbcode.code.copy': '{jslang}wcf.message.bbcode.code.copy{/jslang}',
			'wcf.message.error.editorAlreadyInUse': '{jslang}wcf.message.error.editorAlreadyInUse{/jslang}',
			'wcf.moderation.report.reportContent': '{jslang}wcf.moderation.report.reportContent{/jslang}',
			'wcf.moderation.report.success': '{jslang}wcf.moderation.report.success{/jslang}',
			'wcf.conversation.label.assignLabels': '{jslang}wcf.conversation.label.assignLabels{/jslang}'
		});
		
		var $availableLabels = [ {implode from=$labelList item=label}{ cssClassName: '{if $label->cssClassName}{@$label->cssClassName}{/if}', labelID: {@$label->labelID}, label: '{$label->label|encodeJS}' }{/implode} ];
		var $editorHandler = new WCF.Conversation.EditorHandlerConversation($availableLabels);
		var $inlineEditor = new WCF.Conversation.InlineEditor('.conversation');
		$inlineEditor.setEditorHandler($editorHandler);
		
		{assign var=__supportPaste value=true}
		{if !$conversation->canReply()}{assign var=__supportPaste value=false}{/if}
		{include file='__messageQuoteManager' wysiwygSelector='text' supportPaste=$__supportPaste}
		
		new WCF.Conversation.Message.InlineEditor({@$conversation->conversationID}, $quoteManager);
		new WCF.Conversation.Message.QuoteHandler($quoteManager);
		
		{if $conversation->canReply()}
			require(['WoltLabSuite/Core/Ui/Message/Reply'], function(UiMessageReply) {
				new UiMessageReply({
					ajax: {
						className: 'wcf\\data\\conversation\\message\\ConversationMessageAction'
					},
					quoteManager: $quoteManager
				});
			});
		{/if}
		
		{if $__wcf->session->getPermission('user.profile.canReportContent')}
			new WCF.Moderation.Report.Content('com.woltlab.wcf.conversation.message', '.jsReportConversationMessage');
		{/if}
		new WCF.Conversation.RemoveParticipant({@$conversation->conversationID});
	});
</script>

{include file='footer'}
