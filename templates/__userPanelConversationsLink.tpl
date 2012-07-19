{if MODULE_CONVERSATION && $__wcf->user->userID && $__wcf->session->getPermission('user.conversation.canUseConversation')}
	<li>
		<a href="{link controller='ConversationList'}{/link}">{lang}wcf.conversation.conversations{/lang}{if $__wcf->getConversationHandler()->getUnreadConversationCount()} <span class="badge badgeInverse">{#$__wcf->getConversationHandler()->getUnreadConversationCount()}</span>{/if}</a>
	</li>
{/if}