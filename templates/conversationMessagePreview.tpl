<div class="box48">
	{if $message->getUserProfile()->getAvatar()}
		<a href="{link controller='User' object=$message->getUserProfile()->getDecoratedObject()}{/link}" class="framed">{@$message->getUserProfile()->getAvatar()->getImageTag(48)}</a>
	{/if}

	<div>
		<hgroup class="containerHeadline">
			<h1><a href="{link controller='User' object=$message->getUserProfile()->getDecoratedObject()}{/link}">{$message->username}</a> <small>- {@$message->time|time}</small></h1> 
		</hgroup>
		
		<p>{@$message->getExcerpt()|nl2br}</p>
	</div>
</div>