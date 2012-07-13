{if MODULE_CONVERSATION && $__wcf->user->userID && $__wcf->session->getPermission('user.conversation.canUseConversation')}
	<li>
		<a href="{link controller='ConversationList'}{/link}">{lang}wcf.conversation.conversations{/lang} <span class="badge badgeInverse">96</span></a>
	</li>
{/if}