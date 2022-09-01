{if MODULE_CONVERSATION && $__wcf->user->userID && $__wcf->session->getPermission('user.conversation.canUseConversation') && $__wcf->session->getPermission('user.conversation.canStartConversation') && $user->userID != $__wcf->user->userID}
	<li>
		<a class="jsTooltip" href="{link controller='ConversationAdd'}userID={@$user->userID}{/link}" title="{lang}wcf.conversation.button.add{/lang}">
			{icon size=16 name='comments' type='solid'}
			<span class="invisible">{lang}wcf.conversation.button.add{/lang}</span>
		</a>
	</li>
{/if}
