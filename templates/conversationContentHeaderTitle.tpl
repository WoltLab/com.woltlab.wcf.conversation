<div class="contentHeaderTitle">
	<h1 class="contentTitle">{$conversation->subject}</h1>

	<ul class="inlineList contentHeaderMetaData">
		{hascontent}
			<li>
				{icon name='tags'}
				<div class="labelBadgeInlineList">
					{content}
						{foreach from=$conversation->getAssignedLabels() item=label}
							{unsafe:$label->render()}
						{/foreach}
					{/content}
				</div>
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
