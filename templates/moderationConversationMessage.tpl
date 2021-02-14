<article class="message messageReduced">
	<div class="messageContent">
		<header class="messageHeader">
			<div class="box32 messageHeaderWrapper">
				{user object=$message->getUserProfile() type='avatar32' ariaHidden='true'}
				
				<div class="messageHeaderBox">
					<h2 class="messageTitle">{if $message->getConversation()->canRead()}<a href="{@$message->getLink()}">{$message->getTitle()}</a>{else}{$message->getTitle()}{/if}</h2>
					
					<ul class="messageHeaderMetaData">
						<li>{user object=$message->getUserProfile() class='username'}</li>
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