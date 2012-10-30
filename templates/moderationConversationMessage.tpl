<article class="message messageReduced">
	<div>
		<section class="messageContent">
			<div>
				<header class="messageHeader">
					<div class="messageCredits box32">
						<a href="{link controller='User' object=$message->getUserProfile()->getDecoratedObject()}{/link}" class="framed">{@$message->getUserProfile()->getAvatar()->getImageTag(32)}</a>
						<div>
							<p><a href="{link controller='User' object=$message->getUserProfile()->getDecoratedObject()}{/link}">{$message->getUsername()}</a><p>
							
							{@$message->getTime()|time}
						</div>
					</div>
					
					<h1 class="messageTitle"><a href="{@$message->getLink()}">{$message->getTitle()}</a></h1>
				</header>
				
				<div class="messageBody">
					<div>
						<div class="messageText">
							{@$message->getFormattedMessage()}
						</div>
					</div>
				</div>
			</div>
		</section>
	</div>
</article>