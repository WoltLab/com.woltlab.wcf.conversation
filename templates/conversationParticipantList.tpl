<ul class="containerBoxList tripleColumned conversationParticipantList">
	{foreach from=$participants item=participant}
		<li class="jsParticipant{if !$participant->userID || $participant->hideConversation == 2 || $participant->leftAt > 0} conversationLeft{/if}" data-object-id="{$conversation->getObjectID()}">
			<div class="box24">
				{user object=$participant type='avatar24' ariaHidden='true' tabindex='-1'}
				<div>
					<p>
						{user object=$participant}
						{if $participant->isInvisible}<small>({lang}wcf.conversation.invisible{/lang})</small>{/if}
						{if $participant->userID && ($conversation->userID == $__wcf->getUser()->userID) && ($participant->userID != $__wcf->getUser()->userID) && $participant->hideConversation != 2 && $participant->leftAt == 0}
							<button
								type="button"
								class="jsTooltip jsOnly conversationRemoveParticipant"
								title="{lang}wcf.conversation.participants.removeParticipant{/lang}"
								data-confirm-message="{lang __encode=true}wcf.conversation.participants.removeParticipant.confirmMessage{/lang}"
								data-participant-id="{$participant->getObjectID()}"
								data-conversation-id="{$conversation->getObjectID()}"
							>
								{icon name='xmark'}
							</button>
						{/if}
					</p>
					<dl class="plain inlineDataList small">
						<dt>{lang}wcf.conversation.lastVisitTime{/lang}</dt>
						<dd>{if $participant->lastVisitTime}{time time=$participant->lastVisitTime}{else}-{/if}</dd>
					</dl>
				</div>
			</div>
		</li>
	{/foreach}
</ul>
