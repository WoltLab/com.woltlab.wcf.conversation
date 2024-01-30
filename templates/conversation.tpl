{capture assign='pageTitle'}{$conversation->subject} {if $pageNo > 1} - {lang}wcf.page.pageNo{/lang}{/if}{/capture}

{capture assign='contentHeader'}
	<header class="contentHeader">
		<div class="contentHeaderIcon">
			{@$conversation->getUserProfile()->getAvatar()->getImageTag(64)}
		</div>
		
		<div class="contentHeaderTitle">
			<h1 class="contentTitle jsConversationSubject" data-conversation-id="{$conversation->conversationID}">{$conversation->subject}</h1>
			
			<ul class="inlineList contentHeaderMetaData">
				{hascontent}
					<li>
						{icon name='tags'}
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
					{icon name='user'}
					{user object=$conversation->getUserProfile()}
				</li>
				
				<li>
					{icon name='clock'}
					<a href="{$conversation->getLink()}">{time time=$conversation->time}</a>
				</li>
				
				{if $conversation->isClosed}
					<li>
						<span class="jsIconLock">
							{icon name='lock'}
						</span>
						{lang}wcf.global.state.closed{/lang}
					</li>
				{/if}
			</ul>
		</div>
		
		{hascontent}
			<nav class="contentHeaderNavigation">
				<ul>
					{content}
						{if $conversation->canReply()}
							<li class="jsOnly">
								<button type="button" class="button buttonPrimary jsQuickReply">
									{icon name='reply'}
									<span>{lang}wcf.conversation.message.button.add{/lang}</span>
								</button>
							</li>
						{/if}
						{event name='contentHeaderNavigation'}
					{/content}
				</ul>
			</nav>
		{/hascontent}
	</header>
{/capture}

{capture assign='contentInteractionPagination'}
	{pages print=true assign=pagesLinks controller='Conversation' object=$conversation link="pageNo=%d"}
{/capture}

{capture assign='contentInteractionButtons'}
	<div class="conversation jsConversationInlineEditorContainer contentInteractionButton" data-conversation-id="{$conversation->conversationID}" data-label-ids="[ {implode from=$conversation->getAssignedLabels() item=label}{$label->labelID}{/implode} ]" data-is-closed="{$conversation->isClosed}" data-can-close-conversation="{if $conversation->userID == $__wcf->getUser()->userID}1{else}0{/if}" data-can-add-participants="{if $conversation->canAddParticipants()}1{else}0{/if}" data-is-draft="{if $conversation->isDraft}1{else}0{/if}">
		{if $conversation->isDraft}
			<a href="{link controller='ConversationDraftEdit' id=$conversation->conversationID}{/link}" class="button small jsConversationInlineEditor">
				{icon name='pencil'}
				<span>{lang}wcf.global.button.edit{/lang}</span>
			</a>
		{else}
			<button type="button" class="button small jsConversationInlineEditor">
				{icon name='pencil'}
				<span>{lang}wcf.global.button.edit{/lang}</span>
			</button>
		{/if}
	</div>
{/capture}

{include file='header'}

{if !$conversation->isDraft}
	<section class="section">
		<h2 class="sectionTitle">{lang}wcf.conversation.participants{/lang}</h2>
		
		<ul class="containerBoxList tripleColumned conversationParticipantList jsObjectActionContainer" data-object-action-class-name="wcf\data\conversation\ConversationAction">
			{foreach from=$participants item=participant}
				<li class="jsParticipant jsObjectActionObject{if !$participant->userID || $participant->hideConversation == 2 || $participant->leftAt > 0} conversationLeft{/if}" data-object-id="{$conversation->getObjectID()}">
					<div class="box24">
						{user object=$participant type='avatar24' ariaHidden='true' tabindex='-1'}
						<div>
							<p>
								{user object=$participant}
								{if $participant->isInvisible}<small>({lang}wcf.conversation.invisible{/lang})</small>{/if}
								{if $participant->userID && ($conversation->userID == $__wcf->getUser()->userID) && ($participant->userID != $__wcf->getUser()->userID) && $participant->hideConversation != 2 && $participant->leftAt == 0}
									<button
										type="button"
										class="jsObjectAction jsTooltip jsOnly"
										data-object-action="removeParticipant"
										title="{lang}wcf.conversation.participants.removeParticipant{/lang}"
										data-confirm-message="{lang __encode=true}wcf.conversation.participants.removeParticipant.confirmMessage{/lang}"
										data-object-action-parameter-user-id="{$participant->getObjectID()}"
									>
										{icon name='xmark'}
									</button>
								{/if}
							</p>
							<dl class="plain inlineDataList small">
								<dt>{lang}wcf.conversation.lastVisitTime{/lang}</dt>
								<dd>{if $participant->lastVisitTime}{time time=$participant->lastVisitTime}{else}-{/if}</dd>
							</dl>
						</div>
					</div>
				</li>
			{/foreach}
		</ul>
	</section>
{/if}

<div class="section">
	<ul class="messageList">
		{if $pageNo == 1 && !$conversation->joinedAt|empty}
			<li><woltlab-core-notice type="info">{lang}wcf.conversation.visibility.previousMessages{/lang}</woltlab-core-notice></li>
		{/if}
		{include file='conversationMessageList'}
		{hascontent}
			<li class="messageListPagination">
				{content}{@$pagesLinks}{/content}
			</li>
		{/hascontent}
		{if $conversation->canReply()}{include file='conversationQuickReply'}{/if}
		{if $pageNo == $pages && !$conversation->leftAt|empty}
			<li><woltlab-core-notice type="info">{lang}wcf.conversation.visibility.nextMessages{/lang}</woltlab-core-notice></li>
		{/if}
	</ul>
</div>

{if !ENABLE_DEBUG_MODE}<script src="{$__wcf->getPath()}js/WoltLabSuite.Core.Conversation.min.js?v={@LAST_UPDATE_TIME}"></script>{/if}
<script data-relocate="true" src="{$__wcf->getPath()}js/WCF.Conversation{if !ENABLE_DEBUG_MODE}.min{/if}.js?v={@LAST_UPDATE_TIME}"></script>
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
			'wcf.conversation.label.assignLabels': '{jslang}wcf.conversation.label.assignLabels{/jslang}'
		});
		
		var $availableLabels = [ {implode from=$labelList item=label}{ cssClassName: '{if $label->cssClassName}{@$label->cssClassName|encodeJS}{/if}', labelID: {@$label->labelID}, label: '{$label->label|encodeJS}' }{/implode} ];
		var $editorHandler = new WCF.Conversation.EditorHandlerConversation($availableLabels);
		var $inlineEditor = new WCF.Conversation.InlineEditor('.conversation');
		$inlineEditor.setEditorHandler($editorHandler);
		
		{assign var=__supportPaste value=true}
		{if !$conversation->canReply()}{assign var=__supportPaste value=false}{/if}
		{include file='shared_messageQuoteManager' wysiwygSelector='text' supportPaste=$__supportPaste}
		
		new WCF.Conversation.Message.InlineEditor({@$conversation->conversationID}, $quoteManager);
		
		require(["WoltLabSuite/Core/Conversation/Ui/Message/Quote"], ({ UiConversationMessageQuote }) => {
			new UiConversationMessageQuote($quoteManager);
		});
		
		{if $conversation->canReply()}
			require(['WoltLabSuite/Core/Conversation/Ui/Message/Reply'], function({ Reply }) {
				new Reply({
					ajax: {
						className: 'wcf\\data\\conversation\\message\\ConversationMessageAction'
					},
					quoteManager: $quoteManager
				});
			});
		{/if}
	});
	
	require(['WoltLabSuite/Core/Conversation/Ui/Object/Action/RemoveParticipant'], (UiObjectActionRemoveParticipant) => {
		UiObjectActionRemoveParticipant.setup();
	});
</script>

{include file='footer'}
