<li id="messageQuickReply" class="jsOnly{if $conversation->userID == $__wcf->getUser()->userID} messageGroupStarter{/if}{if $pageNo < $pages} messageQuickReplyCollapsed{/if}" data-object-id="{@$conversation->conversationID}" data-last-post-time="{@$conversation->lastPostTime}" data-page-no="{@$pageNo}">
	<article class="message messageSidebarOrientation{@$__wcf->getStyleHandler()->getStyle()->getVariable('messageSidebarOrientation')|ucfirst}{if $__wcf->getUserProfileHandler()->userOnlineGroupID} userOnlineGroupMarking{@$__wcf->getUserProfileHandler()->userOnlineGroupID}{/if}">
		{include file='messageSidebar' userProfile=$__wcf->getUserProfileHandler()->getUserProfile() isReply=true enableMicrodata=false}
		
		<div class="messageContent messageQuickReplyContent"{if $pageNo < $pages} data-placeholder="{lang}wcf.conversation.reply{/lang}"{/if}>
			<div class="messageBody">
				{if !$conversation->isDraft && !$conversation->hasOtherParticipants()}
					<p class="warning"  role="status" style="margin-bottom: 14px">{lang}wcf.conversation.noParticipantsWarning{/lang}</p>
				{/if}

				{event name='beforeWysiwyg'}
				
				<textarea id="text" name="text" class="wysiwygTextarea"
				          data-autosave="com.woltlab.wcf.conversation.messageAdd-{@$conversation->conversationID}"
				          data-support-mention="true"
				></textarea>
				{include file='messageFormTabsInline' inConversationQuickReply=true}
			</div>
			
			<footer class="messageFooter">
				<div class="formSubmit">
					<button class="buttonPrimary" data-type="save" accesskey="s">{lang}wcf.global.button.reply{/lang}</button>
					{include file='messageFormPreviewButton' previewMessageObjectType='com.woltlab.wcf.conversation.message' previewMessageObjectID=0}
				</div>
			</footer>
		</div>
	</article>
	
	{include file='wysiwyg'}
</li>