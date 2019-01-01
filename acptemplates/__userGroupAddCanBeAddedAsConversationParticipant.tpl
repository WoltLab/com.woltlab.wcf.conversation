{if MODULE_CONVERSATION && ($action == 'add' || $group->groupType > 3)}
	<dl>
		<dt></dt>
		<dd>
			<label><input type="checkbox" id="canBeAddedAsConversationParticipant" name="canBeAddedAsConversationParticipant" value="1"{if $canBeAddedAsConversationParticipant} checked{/if}> {lang}wcf.acp.group.canBeAddedAsConversationParticipant{/lang}</label>
		</dd>
	</dl>
{/if}
