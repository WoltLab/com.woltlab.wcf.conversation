{capture assign='wysiwygSelector'}messageEditor{@$message->messageID}{/capture}
<div class="messageInlineEditor">
	<textarea id="{$wysiwygSelector}" class="wysiwygTextarea"
	          data-autosave="com.woltlab.wcf.conversation.messageEdit-{@$message->messageID}"
	          data-support-mention="true"
	>{$message->message}</textarea>
	{capture assign=wysiwygContainerID}messageEditor{@$message->messageID}{/capture}
	{include file='messageFormTabsInline' inConversationInlineEdit=true wysiwygContainerID=$wysiwygContainerID}
	
	<div class="formSubmit">
		<button type="button" class="button buttonPrimary" data-type="save">{lang}wcf.global.button.save{/lang}</button>
		
		{include file='messageFormPreviewButton' previewMessageFieldID=$wysiwygSelector previewButtonID=$wysiwygSelector|concat:'_PreviewButton' previewMessageObjectType='com.woltlab.wcf.conversation.message' previewMessageObjectID=$message->messageID}
		
		<button type="button" class="button" data-type="cancel">{lang}wcf.global.button.cancel{/lang}</button>
	</div>
	
	{include file='wysiwyg' wysiwygEnableUpload=true}
</div>
