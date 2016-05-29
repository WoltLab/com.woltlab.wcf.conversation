<li id="messageQuickReply" class="jsOnly{if $conversation->userID == $__wcf->getUser()->userID} messageGroupStarter{/if}" data-object-id="{@$conversation->conversationID}" data-last-post-time="{@$conversation->lastPostTime}" data-page-no="{@$pageNo}">
	<article class="message messageSidebarOrientation{@$__wcf->getStyleHandler()->getStyle()->getVariable('messageSidebarOrientation')|ucfirst}{if $__wcf->getUserProfileHandler()->userOnlineGroupID} userOnlineGroupMarking{@$__wcf->getUserProfileHandler()->userOnlineGroupID}{/if}">
		{include file='messageSidebar' userProfile=$__wcf->getUserProfileHandler()}
		
		<div class="messageContent messageQuickReplyContent">
			<div class="messageBody">
				{if !$conversation->isDraft && !$conversation->hasOtherParticipants()}
					<p class="warning" style="margin-bottom: 14px">{lang}wcf.conversation.noParticipantsWarning{/lang}</p>
				{/if}
				
				<textarea id="text" name="text" rows="20" cols="40" style="width: 100%"
				          data-autosave="com.woltlab.wcf.conversation.messageAdd-{@$conversation->conversationID}"
				          data-support-mention="true"
				></textarea>
				{include file='messageFormTabsInline' inConversationQuickReply=true}
			</div>
			
			<footer class="messageFooter">
				<div class="formSubmit">
					<button class="buttonPrimary" data-type="save" accesskey="s">{lang}wcf.global.button.submit{/lang}</button>
				</div>
			</footer>
		</div>
	</article>
	
	<script data-relocate="true">
		require(['WoltLab/WCF/Ui/Message/Reply'], function(UiMessageReply) {
			new UiMessageReply({
				ajax: {
					className: 'wcf\\data\\conversation\\message\\ConversationMessageAction'
				}
			});
		});
	</script>
	{include file='wysiwyg'}
</li>