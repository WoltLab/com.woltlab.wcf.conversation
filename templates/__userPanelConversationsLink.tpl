{if MODULE_CONVERSATION && $__wcf->user->userID && $__wcf->session->getPermission('user.conversation.canUseConversation')}
	<li>
		<a class="jsTooltip" href="{link controller='ConversationList'}{/link}" title="{lang}wcf.conversation.conversations{/lang}"><img src="{icon size='M'}commentInverse{/icon}" alt="" class="icon24" /> <span class="invisible">{lang}wcf.conversation.conversations{/lang}</span>{if $__wcf->getConversationHandler()->getUnreadConversationCount()} <span class="badge badgeInverse">{#$__wcf->getConversationHandler()->getUnreadConversationCount()}</span>{/if}</a>
	</li>
{/if}