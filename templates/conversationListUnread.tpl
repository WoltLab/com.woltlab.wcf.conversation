{foreach from=$conversations item=conversation}
	<li>
		<a href="{link controller='Conversation' object=$conversation}action=firstNew{/link}" class="box24">
			<div class="framed">
				{if $conversation->lastPosterID}
					{@$conversation->getLastPosterProfile()->getAvatar()->getImageTag(24)}
				{else}
					{@$conversation->getUserProfile()->getAvatar()->getImageTag(24)}
				{/if}
			</div>
			<div>
				<h3>{$conversation->subject}</h3>
				<small>{if $conversation->lastPosterID}{$conversation->lastPoster}{else}{$conversation->username}{/if} - {@$conversation->lastPostTime|time}</small>
			</div>
		</a>
	</li>
{/foreach}