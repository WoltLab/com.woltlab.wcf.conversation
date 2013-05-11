{if MODULE_CONVERSATION && $__wcf->user->userID && $__wcf->session->getPermission('user.conversation.canUseConversation')}	
	<li id="unreadConversations" data-count="{#$__wcf->getConversationHandler()->getUnreadConversationCount()}">
		<a href="{link controller='ConversationList'}{/link}"><span class="icon icon16 icon-comments"></span> <span>{lang}wcf.conversation.conversations{/lang}</span> {if $__wcf->getConversationHandler()->getUnreadConversationCount()}<span class="badge badgeInverse">{#$__wcf->getConversationHandler()->getUnreadConversationCount()}</span>{/if}</a>
		<script type="text/javascript" src="{@$__wcf->getPath()}js/WCF.Conversation{if !DEBUG_MODE}.min{/if}.js"></script>
		<script type="text/javascript">
			//<![CDATA[
			$(function() {
				WCF.Language.addObject({
					'wcf.conversation.add': '{lang}wcf.conversation.add{/lang}',
					'wcf.conversation.noMoreItems': '{lang}wcf.conversation.noMoreItems{/lang}',
					'wcf.conversation.showAll': '{lang}wcf.conversation.showAll{/lang}'
				});
				
				new WCF.Conversation.UserPanel('{link controller='ConversationList'}{/link}', '{link controller='ConversationAdd'}{/link}');
			});
			//]]>
		</script>
	</li>
{/if}