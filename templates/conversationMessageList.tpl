{foreach from=$objects item=message}
	{assign var=__modificationLogTime value=$message->time}
	{include file='conversationMessageListLog'}
	
	{if !$conversation|isset && $container|isset}{assign var=conversation value=$container}{/if}
	{assign var='objectID' value=$message->messageID}
	{assign var='userProfile' value=$message->getUserProfile()}
	
	<li id="message{$message->messageID}" class="anchorFixedHeader{if $conversation->userID == $message->userID} messageGroupStarter{/if}">
		<article class="message messageSidebarOrientation{$__wcf->getStyleHandler()->getStyle()->getVariable('messageSidebarOrientation')|ucfirst} jsMessage{if $userProfile->userOnlineGroupID} userOnlineGroupMarking{$userProfile->userOnlineGroupID}{/if}" data-user-id="{$message->userID}" data-can-edit="{if $message->canEdit()}1{else}0{/if}" data-object-id="{$message->messageID}">
			{include file='messageSidebar'}
			
			<div class="messageContent">
				<header class="messageHeader">
					<div class="messageHeaderBox">
						<ul class="messageHeaderMetaData">
							<li><a href="{link controller='Conversation' object=$conversation}messageID={@$message->messageID}{/link}#message{$message->messageID}" class="permalink messagePublicationTime">{time time=$message->time}</a></li>
							
							{event name='messageHeaderMetaData'}
						</ul>
						
						<ul class="messageStatus">
							{if $conversation->isNewMessage($message->getDecoratedObject())}
								<li><span class="badge label newMessageBadge">{lang}wcf.message.new{/lang}</span></li>
							{/if}
							
							{event name='messageStatus'}
						</ul>
					</div>
					
					<ul class="messageQuickOptions">
						<li><a href="{link controller='Conversation' object=$conversation}messageID={@$message->messageID}{/link}#message{$message->messageID}" class="jsTooltip" title="{lang}wcf.conversation.message.permalink{/lang}">#{#$startIndex}</a></li>
						
						{event name='messageQuickOptions'}
					</ul>
					
					{event name='messageHeader'}
				</header>
				
				<div class="messageBody">
					{event name='beforeMessageText'}
					
					<div class="messageText">
						{@$message->getFormattedMessage()}
					</div>
					
					{event name='afterMessageText'}
				</div>
				
				<footer class="messageFooter">
					{include file='attachments'}
					
					{if $message->getUserProfile()->showSignature()}
						<div class="messageSignature">
							<div>{@$message->getUserProfile()->getSignature()}</div>
						</div>
					{/if}
					
					{event name='messageFooter'}
					
					<div class="messageFooterNotes">
						{if $message->editCount}
							<p class="messageFooterNote">{lang}wcf.conversation.message.editNote{/lang}</p>
						{/if}
						
						{event name='messageFooterNotes'}
					</div>
					
					<div class="messageFooterGroup">
						<ul class="messageFooterButtons buttonList smallButtons jsMobileNavigation">
							{if $message->canEdit()}
								<li class="jsOnly">
									<a
										href="{if $conversation->isDraft && $message->messageID == $conversation->firstMessageID}{link controller='ConversationDraftEdit' id=$conversation->conversationID}{/link}{else}#{/if}"
										title="{lang}wcf.conversation.message.edit{/lang}"
										class="button{if !$conversation->isDraft || $message->messageID != $conversation->firstMessageID} jsMessageEditButton{/if}"
									>
										{icon name='pencil'}
										<span>{lang}wcf.global.button.edit{/lang}</span>
									</a>
								</li>
							{/if}
							<li
								class="jsQuoteMessage"
								data-object-id="{$message->messageID}"
								data-is-quoted="{if $__quoteFullQuote|isset && $message->messageID|in_array:$__quoteFullQuote}1{else}0{/if}"
							>
								<a
									href="#"
									title="{lang}wcf.message.quote.quoteMessage{/lang}"
									class="button jsTooltip{if $__quoteFullQuote|isset && $message->messageID|in_array:$__quoteFullQuote} active{/if}"
								>
									{icon name='quote-left' type='solid'}
									<span class="invisible">{lang}wcf.message.quote.quoteMessage{/lang}</span>
								</a>
							</li>
							{if $message->userID != $__wcf->getUser()->userID && $__wcf->session->getPermission('user.profile.canReportContent')}
								<li>
									<button
										type="button"
										title="{lang}wcf.moderation.report.reportContent{/lang}"
										class="button jsTooltip"
										data-report-content="com.woltlab.wcf.conversation.message"
										data-object-id="{$message->messageID}"
									>
										{icon name='triangle-exclamation'}
										<span class="invisible">{lang}wcf.moderation.report.reportContent{/lang}</span>
									</button>
								</li>
							{/if}
							{event name='messageFooterButtons'}
						</ul>
					</div>
				</footer>
			</div>
		</article>
	</li>
	
	{assign var="startIndex" value=$startIndex + 1}
{/foreach}

{assign var=__modificationLogTime value=TIME_NOW}
{include file='conversationMessageListLog'}
