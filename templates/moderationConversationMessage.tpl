<article class="message messageReduced">
	<div class="messageContent">
		<header class="messageHeader">
			<div class="box32 messageHeaderWrapper">
				{if $message->userID}
					<a href="{link controller='User' object=$message->getUserProfile()->getDecoratedObject()}{/link}">{@$message->getUserProfile()->getAvatar()->getImageTag(32)}</a>
				{else}
					<span>{@$message->getUserProfile()->getAvatar()->getImageTag(32)}</span>
				{/if}
				
				<div class="messageHeaderBox">
					<h2 class="messageTitle">{if $message->getConversation()->canRead()}<a href="{@$message->getLink()}">{$message->getTitle()}</a>{else}{$message->getTitle()}{/if}</h2>
					
					<ul class="messageHeaderMetaData">
						<li>{if $message->userID}<a href="{link controller='User' object=$message->getUserProfile()->getDecoratedObject()}{/link}" class="username">{$message->getUsername()}</a>{else}<span class="username">{$message->getUsername()}</span>{/if}</li>
						<li><span class="messagePublicationTime">{@$message->getTime()|time}</span></li>
						
						{event name='messageHeaderMetaData'}
					</ul>
				</div>
			</div>
			
			{event name='messageHeader'}
		</header>
		
		<div class="messageBody">
			{event name='beforeMessageText'}
			
			<div class="messageText">
				{@$message->getFormattedMessage()}
			</div>
			
			{event name='afterMessageText'}
		</div>
	</div>
</article>