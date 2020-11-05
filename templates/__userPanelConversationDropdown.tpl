{if MODULE_CONVERSATION && $__wcf->user->userID && $__wcf->session->getPermission('user.conversation.canUseConversation')}
	<li id="unreadConversations" data-count="{#$__wcf->getConversationHandler()->getUnreadConversationCount()}">
		<a class="jsTooltip" href="{link controller='ConversationList'}{/link}" title="{lang}wcf.conversation.conversations{/lang}"><span class="icon icon32 fa-comments"></span> <span>{lang}wcf.conversation.conversations{/lang}</span> {if $__wcf->getConversationHandler()->getUnreadConversationCount()}<span class="badge badgeUpdate">{#$__wcf->getConversationHandler()->getUnreadConversationCount()}</span>{/if}</a>
		{if !OFFLINE || $__wcf->session->getPermission('admin.general.canViewPageDuringOfflineMode')}
			<script data-relocate="true" src="{@$__wcf->getPath()}js/WCF.Conversation{if !ENABLE_DEBUG_MODE}.min{/if}.js?v={@LAST_UPDATE_TIME}"></script>
			<script data-relocate="true">
				$(function() {
					new WCF.User.Panel.Conversation({
						newConversation: '{jslang}wcf.conversation.add{/jslang}',
						newConversationLink: '{link controller='ConversationAdd' encode=false}{/link}',
						noItems: '{jslang}wcf.conversation.noMoreItems{/jslang}',
						showAllLink: '{link controller='ConversationList' encode=false}{/link}',
						title: '{jslang}wcf.conversation.conversations{/jslang}',
						canStartConversation: {if $__wcf->session->getPermission('user.conversation.canStartConversation')}true{else}false{/if}
					});
				});
			</script>
		{/if}
	</li>
{/if}
