{if MODULE_CONVERSATION && $__wcf->user->userID && $__wcf->session->getPermission('user.conversation.canUseConversation')}
	<li id="unreadConversations" data-count="{#$__wcf->getConversationHandler()->getUnreadConversationCount()}">
		<a
			class="jsTooltip"
			href="{link controller='ConversationList'}{/link}"
			title="{lang}wcf.conversation.conversations{/lang}"
			role="button"
			tabindex="0"
			aria-haspopup="true"
			aria-expanded="false"
		>
			{icon size=32 name='comments' type='solid'}
			<span>{lang}wcf.conversation.conversations{/lang}</span>
			{if $__wcf->getConversationHandler()->getUnreadConversationCount()}
				<span class="badge badgeUpdate">{#$__wcf->getConversationHandler()->getUnreadConversationCount()}</span>
			{/if}
		</a>
		{if !OFFLINE || $__wcf->session->getPermission('admin.general.canViewPageDuringOfflineMode')}
			<script data-relocate="true" src="{@$__wcf->getPath()}js/WCF.Conversation{if !ENABLE_DEBUG_MODE}.min{/if}.js?v={@LAST_UPDATE_TIME}"></script>
			<script data-relocate="true">
				require(["WoltLabSuite/Core/Conversation/Ui/User/Menu/Data/Conversation"], ({ setup }) => {
					setup({
						canStartConversation: {if $__wcf->session->getPermission('user.conversation.canStartConversation')}true{else}false{/if},
						newConversationLink: '{link controller='ConversationAdd' encode=false}{/link}',
						newConversationTitle: '{jslang}wcf.conversation.add{/jslang}',
						noItems: '{jslang}wcf.conversation.noMoreItems{/jslang}',
						showAllLink: '{link controller='ConversationList' encode=false}{/link}',
						showAllTitle: '{jslang}wcf.conversation.showAll{/jslang}',
						title: '{jslang}wcf.conversation.conversations{/jslang}',
					});
				});
			</script>
		{/if}
	</li>
{/if}
