{if MODULE_CONVERSATION && $__wcf->user->userID && $__wcf->session->getPermission('user.conversation.canUseConversation') && $__wcf->session->getPermission('user.conversation.canStartConversation') && $user->userID != $__wcf->user->userID}<li><a href="{link controller='ConversationAdd'}userID={@$user->userID}{/link}">{lang}wcf.conversation.button.add{/lang}</a></li>{/if}
