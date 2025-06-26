<div class="contentHeaderTitle">
	<h1 class="contentTitle jsConversationSubject" data-conversation-id="{$conversation->conversationID}">{$conversation->subject}</h1>

	<ul class="inlineList contentHeaderMetaData">
		{hascontent}
			<li>
				{icon name='tags'}
				<ul class="labelList">
					{content}
						{foreach from=$conversation->getAssignedLabels() item=label}
							<li><span class="label badge{if $label->cssClassName} {$label->cssClassName}{/if}">{$label->label}</span></li>
						{/foreach}
					{/content}
				</ul>
			</li>
		{/hascontent}

		<li>
			{icon name='user'}
			{user object=$conversation->getUserProfile()}
		</li>

		<li>
			{icon name='clock'}
			<a href="{$conversation->getLink()}">{time time=$conversation->time}</a>
		</li>

		{if $conversation->isClosed}
			<li>
				<span class="jsIconLock">
					{icon name='lock'}
				</span>
				{lang}wcf.global.state.closed{/lang}
			</li>
		{/if}
	</ul>
</div>
