{if MODULE_CONVERSATION && $__wcf->user->userID && $__wcf->session->getPermission('user.conversation.canUseConversation')}
	<li class="menuOverlayItem" data-more="com.woltlab.wcf.conversation">
		<a href="{link controller='ConversationList'}{/link}" class="menuOverlayItemLink menuOverlayItemBadge box24" data-badge-identifier="unreadConversations">
			<span class="icon icon24 fa-comments"></span>
			<span class="menuOverlayItemTitle">{lang}wcf.conversation.conversations{/lang}</span>
			{if $__wcf->getConversationHandler()->getUnreadConversationCount()}<span class="badge badgeUpdate">{#$__wcf->getConversationHandler()->getUnreadConversationCount()}</span>{/if}
		</a>
	</li>
{/if}
