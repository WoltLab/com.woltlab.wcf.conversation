<li id="messageQuickReply" class="marginTop shadow javascriptOnly{if $conversation->userID == $__wcf->getUser()->userID} wbbThreadStarter{/if}" style="display: none;" data-conversation-id="{@$conversation->conversationID}" data-last-post-time="{@$conversation->lastPostTime}" data-page-no="{@$pageNo}">
	<article class="wbbPost message messageSidebarOrientationLeft dividers">
		<div>
			{include file='messageSidebar'}
			
			<section class="messageContent messageQuickReplyContent">
				<div>
					<header class="messageHeader">
						<span class="pointer"><span></span></span>
					</header>
					
					<div class="messageBody">
						<textarea id="text" name="text" rows="20" cols="40" style="width: 100%"></textarea>
					</div>
					
					<div class="formSubmit">
						<button class="buttonPrimary" data-type="save">{lang}wbb.post.button.add{/lang}</button>
						<button data-type="extended">{lang}wbb.post.button.add.extended{/lang}</button>
						<button data-type="cancel">{lang}wcf.global.button.cancel{/lang}</button>
					</div>
				</div>
			</section>
		</div>
	</article>
	
	{include file='wysiwyg'}
</li>