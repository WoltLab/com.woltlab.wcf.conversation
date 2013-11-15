<div class="messageInlineEditor">
	<textarea id="messageEditor{@$message->messageID}" rows="20" cols="40">{$message->message}</textarea>
	
	<div class="formSubmit">
		<button class="buttonPrimary" data-type="save">{lang}wcf.global.button.submit{/lang}</button>
		<button data-type="extended">{lang}wcf.message.button.extendedEdit{/lang}</button>
		<button data-type="cancel">{lang}wcf.global.button.cancel{/lang}</button>
	</div>
	
	<script data-relocate="true">
		//<![CDATA[
		$(function() {
			WCF.System.Dependency.Manager.register('CKEditor', function() { new WCF.Message.UserMention('messageEditor{@$message->messageID}'); });
		});
		//]]>
	</script>
	{include file='wysiwyg'}
</div>