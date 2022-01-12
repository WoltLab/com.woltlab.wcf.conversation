{if $__searchAreaInitialized|empty && $templateName|substr:0:12 == 'conversation'}
	{assign var='__searchObjectTypeName' value='com.woltlab.wcf.conversation.message'}
	
	{if $__wcf->getActivePage()->identifier == 'com.woltlab.wcf.conversation.Conversation'}
		{capture assign='__searchTypeLabel'}{lang}wcf.search.type.com.woltlab.wcf.conversation{/lang}{/capture}
		{capture assign='__searchTypesScoped'}<li><a href="#" data-extended-link="{link controller='Search'}extended=1&type=com.woltlab.wcf.conversation.message{/link}" data-object-type="com.woltlab.wcf.conversation.message" data-parameters='{ "conversationID": {@$conversation->conversationID} }'>{lang}wcf.search.type.com.woltlab.wcf.conversation{/lang}</a></li>{/capture}
	{/if}
{/if}
