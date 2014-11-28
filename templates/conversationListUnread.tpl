{foreach from=$conversations item=conversation}
	<li class="conversationItem{if $conversation->lastVisitTime < $conversation->lastPostTime} conversationItemUnread{/if}">
		<a href="{link controller='Conversation' object=$conversation}action=firstNew{/link}" class="box24">
			<div class="framed">
				{if $conversation->lastPosterID}
					{@$conversation->getLastPosterProfile()->getAvatar()->getImageTag(24)}
				{else}
					{@$conversation->getUserProfile()->getAvatar()->getImageTag(24)}
				{/if}
			</div>
			<div>
				<h3>{if $conversation->lastVisitTime < $conversation->lastPostTime}<span class="badge label newContentBadge">{lang}wcf.message.new{/lang}</span> {/if}{$conversation->subject}</h3>
				<small>{if $conversation->lastPosterID}{$conversation->lastPoster}{else}{$conversation->username}{/if} - {@$conversation->lastPostTime|time}</small>
			</div>
		</a>
	</li>
{/foreach}