{foreach from=$conversations item=conversation}
	<li>
		<a href="{link controller='Conversation' id=$conversation[conversationID] title=$conversation[subject]}{/link}" class="box24">
			<div class="framed">
				{@$userProfiles[$conversation[lastPosterID]]->getAvatar()->getImageTag(24)}
			</div>
			<hgroup>
				<h1>{$conversation[subject]}</h1>
				<h2><small>{@$conversation[lastPostTime]|time}</small></h2>
			</hgroup>
		</a>
	</li>
{/foreach}