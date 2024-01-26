{if MODULE_CONVERSATION && $__wcf->user->userID && $__wcf->session->getPermission('user.conversation.canUseConversation') && $__wcf->session->getPermission('user.conversation.canStartConversation') && $user->userID != $__wcf->user->userID}
	<a class="userCard__button jsTooltip" href="{link controller='ConversationAdd'}userID={@$user->userID}{/link}" title="{lang}wcf.conversation.button.add{/lang}">{icon name='comments' type='solid' size=24}</a>
{/if}
