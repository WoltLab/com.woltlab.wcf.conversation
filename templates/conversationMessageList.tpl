{foreach from=$objects item=message}
	{if !$conversation|isset && $container|isset}{assign var=conversation value=$container}{/if}
	{assign var='objectID' value=$message->messageID}
	{assign var='userProfile' value=$message->getUserProfile()}
	
	<li id="message{@$message->messageID}" class="marginTop{if MESSAGE_SIDEBAR_ENABLE_MESSAGE_GROUP_STARTER_ICON && $conversation->userID == $message->userID} messageGroupStarter{/if}">
		<article class="message messageSidebarOrientationLeft dividers jsMessage" data-can-edit="{if $message->canEdit()}1{else}0{/if}" data-object-id="{@$message->messageID}">
			<div>
				{include file='messageSidebar'}
				
				<section class="messageContent">
					<div>
						<header class="messageHeader">
							<hgroup class="messageHeadline">
								<h2><a href="{link controller='Conversation' object=$conversation}messageID={@$message->messageID}{/link}#message{@$message->messageID}" class="permalink">{@$message->time|time}</a></h2>
							</hgroup>
							
							{if $conversation->isNewMessage($message->getDecoratedObject())}
								<p class="newMessageBadge">{lang}wcf.message.new{/lang}</p>
							{/if}
						</header>
						
						<div class="messageBody">
							<div>
								<div class="messageText">
									{@$message->getFormattedMessage()}
								</div>
							</div>
							
							{include file='attachments'}
							
							{if $message->getUserProfile()->signatureCache}
								<div class="messageSignature">
									<div>{@$message->getUserProfile()->signatureCache}</div>
								</div>
							{/if}
							
							<div class="messageFooter"></div>
							
							<footer class="messageOptions">
								<nav>
									<ul class="smallButtons buttonGroup">{*
										*}{if $message->canEdit()}<li><a href="{link controller='ConversationMessageEdit' id=$message->messageID}{/link}" title="{lang}wcf.conversation.message.edit{/lang}" class="button jsMessageEditButton"><span class="icon icon16 icon-pencil"></span> <span>{lang}wcf.global.button.edit{/lang}</span></a></li>{/if}{*
										*}<li class="jsQuoteMessage" data-object-id="{@$message->messageID}" data-is-quoted="{if $__quoteFullQuote|isset && $message->messageID|in_array:$__quoteFullQuote}1{else}0{/if}"><a href="{link controller='ConversationMessageAdd' id=$conversation->conversationID quoteMessageID=$message->messageID}{/link}" title="{lang}wcf.message.quote.quoteMessage{/lang}" class="button jsTooltip{if $__quoteFullQuote|isset && $message->messageID|in_array:$__quoteFullQuote} active{/if}"><span class="icon icon16 icon-quote-left"></span> <span class="invisible">{lang}wcf.message.quote.quoteMessage{/lang}</span></a></li>{*
										*}{if $message->userID != $__wcf->getUser()->userID}<li class="jsReportConversationMessage" data-object-id="{@$message->messageID}"><a title="{lang}wcf.moderation.report.reportContent{/lang}" class="button jsTooltip"><span class="icon icon16 icon-warning-sign"></span></a></li>{/if}{*
										*}<li class="toTopLink"><a href="{@$__wcf->getAnchor('top')}" title="{lang}wcf.global.scrollUp{/lang}" class="button jsTooltip"><span class="icon icon16 icon-arrow-up"></span> <span class="invisible">{lang}wcf.global.scrollUp{/lang}</span></a></li>{*
									*}</ul>
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
