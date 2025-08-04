{capture assign='pageTitle'}{$conversation->subject}{if $pageNo > 1} - {lang}wcf.page.pageNo{/lang}{/if}{/capture}

{capture assign='contentHeader'}
	<header class="contentHeader">
		<div class="contentHeaderIcon">
			{@$conversation->getUserProfile()->getAvatar()->getImageTag(64)}
		</div>

		{include file='conversationContentHeaderTitle'}
		
		{hascontent}
			<nav class="contentHeaderNavigation">
				<ul>
					{content}
						{if $conversation->canReply()}
							<li class="jsOnly">
								<button type="button" class="button buttonPrimary jsQuickReply">
									{icon name='reply'}
									<span>{lang}wcf.conversation.message.button.add{/lang}</span>
								</button>
							</li>
						{/if}
						{event name='contentHeaderNavigation'}
					{/content}
				</ul>
			</nav>
		{/hascontent}
	</header>
{/capture}

{capture assign='contentInteractionPagination'}
	{pages print=true assign=pagesLinks controller='Conversation' object=$conversation link="pageNo=%d"}
{/capture}

{capture assign='contentInteractionButtons'}
	<div class="conversation contentInteractionButton">
		{unsafe:$interactionContextMenu->render()}
	</div>
{/capture}

{include file='header'}

{if !$conversation->isDraft}
	<section class="section">
		<h2 class="sectionTitle">{lang}wcf.conversation.participants{/lang}</h2>
		
		{include file='conversationParticipantList'}
	</section>
{/if}

<div class="section">
	<ul class="messageList">
		{if $pageNo == 1 && !$conversation->joinedAt|empty}
			<li><woltlab-core-notice type="info">{lang}wcf.conversation.visibility.previousMessages{/lang}</woltlab-core-notice></li>
		{/if}
		{include file='conversationMessageList'}
		{hascontent}
			<li class="messageListPagination">
				{content}{@$pagesLinks}{/content}
			</li>
		{/hascontent}
		{if $conversation->canReply()}{include file='conversationQuickReply'}{/if}
		{if $pageNo == $pages && !$conversation->leftAt|empty}
			<li><woltlab-core-notice type="info">{lang}wcf.conversation.visibility.nextMessages{/lang}</woltlab-core-notice></li>
		{/if}
	</ul>
</div>

<script data-relocate="true">
	{if $conversation->canReply()}
		require(['WoltLabSuite/Core/Conversation/Ui/Message/Reply'], function({ Reply }) {
			new Reply({
				ajax: {
					className: 'wcf\\data\\conversation\\message\\ConversationMessageAction'
				},
			});
		});
	{/if}

	require([
		'WoltLabSuite/Core/Conversation/Ui/Message/InlineEditor',
		'WoltLabSuite/Core/Component/Quote/Message',
		'WoltLabSuite/Core/Api/Conversations/GetParticipantList',
	], ({ UiConversationMessageInlineEditor }, { registerContainer }, { getParticipantList }) => {
		new UiConversationMessageInlineEditor({$conversation->conversationID});

		registerContainer(".message", ".messageBody", "com.woltlab.wcf.conversation.message");

		const contextMenu = document.getElementById('{unsafe:$interactionContextMenu->getContainerID()|encodeJS}')
		console.log(contextMenu);
		contextMenu.addEventListener('interaction:invalidate', () => reloadConversationParticipantList())
		contextMenu.addEventListener('interaction:invalidate-all', () => reloadConversationParticipantList())

		function reloadConversationParticipantList () {
			void getParticipantList({$conversation->conversationID}).then((response) => {
				if (!response.ok) {
					return;
				}

				const participantList = document.querySelector('.conversationParticipantList');
				participantList.outerHTML = response.value;
			});
		}
	});
</script>

{include file='footer'}
