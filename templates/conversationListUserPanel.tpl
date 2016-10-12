{foreach from=$conversations item=conversation}
	<li class="conversationItem{if $conversation->lastVisitTime < $conversation->lastPostTime} conversationItemUnread interactiveDropdownItemOutstanding{/if}" data-link="{link controller='Conversation' object=$conversation}action=firstNew{/link}" data-object-id="{@$conversation->conversationID}" data-is-read="{if $conversation->lastVisitTime < $conversation->lastPostTime}false{else}true{/if}">
		<div class="box48">
			<div>
				{if $conversation->userID == $__wcf->user->userID}
					{if $conversation->participants > 1}
						<span class="icon icon48 fa-users"></span>
					{else}
						{@$conversation->getOtherParticipantProfile()->getAvatar()->getImageTag(48)}
					{/if}	
				{else}
					{@$conversation->getUserProfile()->getAvatar()->getImageTag(48)}
				{/if}
			</div>
			<div>
				<h3><a href="{link controller='Conversation' object=$conversation}action=firstNew{/link}">{$conversation->subject}</a></h3>
				<small class="conversationInfo">
					<span class="conversationParticipant">
						{if $conversation->userID == $__wcf->user->userID}
							{if $conversation->participants > 1}
								{assign var='participantSummaryCount' value=$conversation->getParticipantSummary()|count}
								{implode from=$conversation->getParticipantSummary() item=participant}<a href="{link controller='User' object=$participant}{/link}" class="userLink{if $participant->hideConversation == 2} conversationLeft{/if}" data-user-id="{@$participant->userID}">{$participant->username}</a>{/implode}
								{if $participantSummaryCount < $conversation->participants}{lang}wcf.conversation.participants.other{/lang}{/if}
							{else}
								{if $conversation->getOtherParticipantProfile()->userID}<a href="{link controller='User' id=$conversation->getOtherParticipantProfile()->userID title=$conversation->getOtherParticipantProfile()->username}{/link}">{$conversation->getOtherParticipantProfile()->username}</a>{else}{$conversation->getOtherParticipantProfile()->username}{/if}
							{/if}
						{else}
							{if $conversation->userID}<a href="{link controller='User' id=$conversation->userID title=$conversation->username}{/link}">{$conversation->username}</a>{else}{$conversation->username}{/if}
						{/if}
					</span>
					
					<span class="conversationLastPostTime">{@$conversation->lastPostTime|time}</span>
				</small>
			</div>
		</div>
	</li>
{/foreach}
