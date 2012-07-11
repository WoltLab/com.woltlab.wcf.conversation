{foreach from=$objects item=message}
	{assign var='objectID' value=$message->messageID}
	{assign var='userProfile' value=$message->getUserProfile()}
	
	<li id="message{@$message->messageID}" class="marginTop shadow{if $conversation->userID == $message->userID} wbbThreadStarter{/if}">
		<article class="wbbPost message messageSidebarOrientationLeft dividers">
			<div>
				{include file='messageSidebar'}
				
				<section class="messageContent">
					<div>
						<header class="messageHeader">
							<p class="messageCounter">
								<a href="{link controller='Conversation' object=$conversation}messageID={@$message->messageID}{/link}#message{@$message->messageID}" title="{lang}wcf.conversation.message.permalink{/lang}" class="button jsTooltip">{#$startIndex}</a>
							</p>
							
							{if $message->time > $conversation->lastVisitTime}<p class="newMessageBadge">{lang}wcf.conversation.message.new{/lang}</p>{/if}
							
							{@$message->time|time}
							
							<span class="pointer"><span></span></span>
						</header>
						
						<div class="messageBody">
							<div>
								<div class="messageText">
									{@$message->getFormattedMessage()}
								</div>
								
								{include file='attachments'}
							</div>
							
							{if $message->getUserProfile()->signatureCache}
								<div class="messageSignature">
									<div>{@$message->getUserProfile()->signatureCache}</div>
								</div>
							{/if}
							
							<div class="messageFooter">
								{*if $post->editCount}
									<p class="wbbPostEditNote messageFooterNote">{lang}wbb.post.editNote{/lang}</p>
								{/if*}
							</div>
							
							<footer class="contentOptions clearfix">
								<nav>
									<ul class="smallButtons">
										{if $message->userID == $__wcf->user->userID}<li><a href="{link controller='ConversationMessageEdit' id=$message->messageID}{/link}" title="{lang}wcf.conversation.message.edit{/lang}" class="button wbbPostEditButton"><img src="{icon size='S'}edit{/icon}" alt="" class="icon16" /> <span>{lang}wcf.global.button.edit{/lang}</span></a></li>{/if}
										<li class="toTopLink"><a href="{@$__wcf->getAnchor('top')}" title="{lang}wcf.global.scrollUp{/lang}" class="button jsTooltip"><img src="{icon size='S'}circleArrowUp{/icon}" alt="" class="icon16" /> <span class="invisible">{lang}wcf.global.scrollUp{/lang}</span></a></li>
									</ul>
								</nav>
							</footer>
						</div>
					</div>
				</section>
			</div>
		</article>
	</li>
	
	{if $sortOrder == 'DESC'}
		{assign var='startIndex' value=$startIndex-1}
	{else}
		{assign var='startIndex' value=$startIndex+1}
	{/if}
{/foreach}
