{foreach from=$conversations item=conversation}
	<li>
		<a href="{link controller='Conversation' object=$conversation}{/link}" class="box24">
			<div class="framed">
				{if $conversation->lastPosterID}
					{@$conversation->getLastPosterProfile()->getAvatar()->getImageTag(24)}
				{else}
					{@$conversation->getUserProfile()->getAvatar()->getImageTag(24)}
				{/if}
			</div>
			<hgroup>
				<h1>{$conversation->subject}</h1>
				<h2><small>{if $conversation->lastPosterID}{$conversation->lastPoster}{else}{$conversation->username}{/if} - {@$conversation->lastPostTime|time}</small></h2>
			</hgroup>
		</a>
	</li>
{/foreach}