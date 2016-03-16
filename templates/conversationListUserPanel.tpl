{foreach from=$conversations item=conversation}
	<li class="conversationItem{if $conversation->lastVisitTime < $conversation->lastPostTime} conversationItemUnread interactiveDropdownItemOutstanding{/if}" data-link="{link controller='Conversation' object=$conversation}action=firstNew{/link}" data-object-id="{@$conversation->conversationID}" data-is-read="{if $conversation->lastVisitTime < $conversation->lastPostTime}false{else}true{/if}">
		<div class="box48">
			<div>
				{if $conversation->lastPosterID}
					{@$conversation->getLastPosterProfile()->getAvatar()->getImageTag(48)}
				{else}
					{@$conversation->getUserProfile()->getAvatar()->getImageTag(48)}
				{/if}
			</div>
			<div>
				<h3><a href="{link controller='Conversation' object=$conversation}action=firstNew{/link}">{$conversation->subject}</a></h3>
				<small>{if $conversation->lastPosterID}<a href="{link controller='User' id=$conversation->lastPosterID title=$conversation->lastPoster}{/link}">{$conversation->lastPoster}</a>{else}{$conversation->username}{/if} <span class="separatorLeft">{@$conversation->lastPostTime|time}</span></small>
			</div>
		</div>
	</li>
{/foreach}
