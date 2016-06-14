{if $__searchAreaInitialized|empty && $templateName|substr:0:12 == 'conversation'}
	{capture assign='__searchInputPlaceholder'}{if $conversation|isset}{lang}wcf.conversation.searchConversation{/lang}{else}{lang}wcf.conversation.searchConversations{/lang}{/if}{/capture}
	{capture assign='__searchHiddenInputFields'}<input type="hidden" name="types[]" value="com.woltlab.wcf.conversation.message">{if $conversation|isset}<input type="hidden" name="conversationID" value="{@$conversation->conversationID}">{/if}{/capture}
	{assign var='__searchAreaInitialized' value=true}
{/if}
