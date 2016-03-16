{if MODULE_CONVERSATION && $__wcf->user->userID && $__wcf->session->getPermission('user.conversation.canUseConversation')}
	<li id="unreadConversations" data-count="{#$__wcf->getConversationHandler()->getUnreadConversationCount()}">
		<a class="jsTooltip" href="{link controller='ConversationList'}{/link}" title="{lang}wcf.conversation.conversations{/lang}"><span class="icon icon32 fa-comments"></span> <span>{lang}wcf.conversation.conversations{/lang}</span> {if $__wcf->getConversationHandler()->getUnreadConversationCount()}<span class="badge badgeUpdate">{#$__wcf->getConversationHandler()->getUnreadConversationCount()}</span>{/if}</a>
		{if !OFFLINE || $__wcf->session->getPermission('admin.general.canViewPageDuringOfflineMode')}
			<script data-relocate="true" src="{@$__wcf->getPath()}js/WCF.Conversation{if !ENABLE_DEBUG_MODE}.min{/if}.js?v={@LAST_UPDATE_TIME}"></script>
			<script data-relocate="true">
				//<![CDATA[
				$(function() {
					new WCF.User.Panel.Conversation({
						markAllAsReadConfirmMessage: '{lang}wcf.conversation.markAllAsRead.confirmMessage{/lang}',
						newConversation: '{lang}wcf.conversation.add{/lang}',
						newConversationLink: '{link controller='ConversationAdd' encode=false}{/link}',
						noItems: '{lang}wcf.conversation.noMoreItems{/lang}',
						showAllLink: '{link controller='ConversationList' encode=false}{/link}',
						title: '{lang}wcf.conversation.conversations{/lang}'
					});
				});
				//]]>
			</script>
		{/if}
	</li>
{/if}