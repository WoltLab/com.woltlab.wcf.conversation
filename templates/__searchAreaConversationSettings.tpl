{if $__searchAreaInitialized|empty && $templateName|substr:0:12 == 'conversation'}
	{capture assign='__searchInputPlaceholder'}{lang}wcf.conversation.searchConversations{/lang}{/capture}
	{capture assign='__searchHiddenInputFields'}<input type="hidden" name="types[]" value="com.woltlab.wcf.conversation.message" />{/capture}
{/if}