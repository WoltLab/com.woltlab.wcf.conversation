{if MODULE_CONVERSATION && $__wcf->user->userID && $__wcf->session->getPermission('user.conversation.canUseConversation')}	
	{if $__wcf->getConversationHandler()->getUnreadConversationCount()}
		{* dropdown menu with the x unread conversations *}
		<li id="unreadConversations" class="dropdown">
			<a class="dropdownToggle" data-toggle="unreadConversations">
				<img src="{icon size='M'}commentInverse{/icon}" alt="" class="icon24" />
				<span class="invisible">{lang}wcf.conversation.conversations{/lang}</span>
				<span class="badge badgeInverse">{#$__wcf->getConversationHandler()->getUnreadConversationCount()}</span>
			</a>
			<ul class="dropdownMenu">
				<li><span>{lang}wcf.global.loading{/lang}</span></li>
				<li class="dropdownDivider"></li>
				<li><a href="{link controller='ConversationList'}{/link}">{lang}wcf.conversation.showAll{/lang}</a></li>
			</ul>
			<script type="text/javascript" src="{@$__wcf->getPath()}js/WCF.Conversation.js"></script>
			<script type="text/javascript">
				//<![CDATA[
				$(function() {
					new WCF.Conversation.UserPanel();
				});
				//]]>
			</script>
		</li>
	{else}
		{* static link to conversations *}
		<li>
			<a class="jsTooltip" href="{link controller='ConversationList'}{/link}" title="{lang}wcf.conversation.conversations{/lang}"><img src="{icon size='M'}commentInverse{/icon}" alt="" class="icon24" /> <span class="invisible">{lang}wcf.conversation.conversations{/lang}</span></a>
		</li>
	{/if}
{/if}