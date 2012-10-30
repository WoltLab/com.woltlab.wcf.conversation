<article class="message messageReduced marginTop shadow">
	<div>
		<section class="messageContent">
			<div>
				<header class="messageHeader">
					<p class="messageCounter"><a href="{link controller='Conversation' object=$item[message]->getConversation()}messageID={@$item[message]->messageID}{/link}#message{@$item[message]->messageID}" title="{lang}wcf.conversation.message.permalink{/lang}" class="button jsTooltip">{#$startIndex}</a></p>
					
					<div class="messageCredits box32">
						{if $item[message]->getUserProfile()->getAvatar()}
							<a href="{link controller='User' object=$item[message]->getUserProfile()}{/link}" class="framed">{@$item[message]->getUserProfile()->getAvatar()->getImageTag(32)}</a>
						{/if}
						<div>
							<p><a href="{link controller='User' object=$item[message]->getUserProfile()}{/link}" class="userLink" data-user-id="{@$item[message]->userID}">{$item[message]->username}</a><p>
							
							{@$item[message]->time|time}
						</div>
					</div>
					
					<h1 class="messageTitle"><a href="{link controller='Conversation' object=$item[message]->getConversation()}messageID={@$item[message]->messageID}&highlight={$query|urlencode}{/link}#message{@$item[message]->messageID}">{$item[message]->subject}</a></h1>
				</header>
				
				<div class="messageBody">
					<div>
						{@$item[message]->getFormattedMessage()}
					</div>
					
					<footer class="messageOptions clearfix">
						<nav class="breadcrumbs marginTop">
							<ul>
								<li><a href="{link controller='Conversation' object=$item[message]->getConversation()}highlight={$query|urlencode}{/link}" title="{$item[message]->subject}"><span>{$item[message]->subject}</span></a> <span class="pointer"><span>&raquo;</span></span></li>
							</ul>
						</nav>
						
						<nav>
							<ul class="smallButtons">
								<li class="toTopLink"><a href="{@$__wcf->getAnchor('top')}" title="{lang}wcf.global.scrollUp{/lang}" class="button jsTooltip"><img src="{icon}circleArrowUp{/icon}" alt="" /> <span class="invisible">{lang}wcf.global.scrollUp{/lang}</span></a></li>
							</ul>
						</nav>
					</footer>
				</div>
			</div>
		</section>
	</div>
</article>
