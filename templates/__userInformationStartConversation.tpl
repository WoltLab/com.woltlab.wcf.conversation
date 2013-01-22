{if MODULE_CONVERSATION && $__wcf->user->userID && $__wcf->session->getPermission('user.conversation.canUseConversation') && $user->userID != $__wcf->user->userID}
	<li><a class="jsTooltip" href="{link controller='ConversationAdd'}userID={@$user->userID}{/link}" title="{lang}wcf.conversation.button.add{/lang}"><span class="icon icon16 icon-comment"></span></a></li>
{/if}