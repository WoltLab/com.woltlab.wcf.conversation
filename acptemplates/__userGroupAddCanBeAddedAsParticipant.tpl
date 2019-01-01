{if MODULE_CONVERSATION && ($action == 'add' || $group->groupType > 3)}
	<dl>
		<dt></dt>
		<dd>
			<label><input type="checkbox" id="canBeAddedAsParticipant" name="canBeAddedAsParticipant" value="1"{if $canBeAddedAsParticipant} checked{/if}> {lang}wcf.acp.group.canBeAddedAsParticipant{/lang}</label>
		</dd>
	</dl>
{/if}
