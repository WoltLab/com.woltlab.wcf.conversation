<div class="messageInlineEditor">
	<textarea id="messageEditor{@$message->messageID}" rows="20" cols="40"
	          data-autosave="com.woltlab.wcf.conversation.messageEdit-{@$message->messageID}"
	          data-support-mention="true"
	>{$message->message}</textarea>
	{capture assign=wysiwygContainerID}messageEditor{@$message->messageID}{/capture}
	{include file='messageFormTabsInline' inConversationInlineEdit=true wysiwygContainerID=$wysiwygContainerID}
	
	<div class="formSubmit">
		<button class="buttonPrimary" data-type="save">{lang}wcf.global.button.submit{/lang}</button>
		<button data-type="cancel">{lang}wcf.global.button.cancel{/lang}</button>
	</div>
	
	{include file='wysiwyg' wysiwygEnableUpload=true}
	<script data-relocate="true">
		//<![CDATA[
		$(function() {
			WCF.System.Dependency.Manager.register('Redactor_messageEditor{@$message->messageID}', function() { new WCF.Message.UserMention('messageEditor{@$message->messageID}'); });
		});
		//]]>
	</script>
</div>
