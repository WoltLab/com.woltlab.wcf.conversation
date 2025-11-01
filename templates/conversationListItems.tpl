{foreach from=$view->getItems() item='conversation'}
	<article class="listView__item discussionList__item conversationList__item" data-object-id="{$conversation->getObjectID()}">
		<div class="discussionList__item__header">
			<div class="discussionList__item__meta">
				<div class="discussionList__item__meta__avatar">
					{unsafe:$conversation->getUserProfile()->getAvatar()->getImageTag(32)}
				</div>

				<div class="discussionList__item__meta__content">
					<div class="discussionList__item__meta__author">
						{unsafe:$conversation->getUserProfile()->getFormattedUsername()}
					</div>
					
					<div class="discussionList__item__meta__time">
						{time time=$conversation->time}
					</div>
				</div>

				{event name='meta'}
			</div>

			<div class="discussionList__item__toolbar">
				{hascontent}
					<div class="discussionList__item__status">
						{content}
							{if $conversation->isClosed}
								<span class="jsTooltip" title="{lang}wcf.global.state.closed{/lang}">
									{icon name='lock'}
								</span>
							{/if}

							{event name='status'}
						{/content}
					</div>
				{/hascontent}

				<div class="discussionList__item__interactions">
					{if $view->hasBulkInteractions()}
						<label class="listView__selectItem__label jsTooltip" title="{lang}wcf.clipboard.item.mark{/lang}">
							<input type="checkbox" class="listView__selectItem" aria-label="{lang}wcf.clipboard.item.mark{/lang}">
						</label>
					{/if}

					{unsafe:$view->renderInteractionContextMenuButton($conversation)}
				</div>
			</div>
		</div>

		<div class="discussionList__item__content">
			{if $conversation->isNew()}
				{unsafe:$view->renderMarkAsReadButton($conversation)}
			{/if}
			
			<h2 class="discussionList__item__title">
				<a href="{if $conversation->isNew()}{link controller='Conversation' object=$conversation action='firstNew'}{/link}{else}{$conversation->getLink()}{/if}" class="discussionList__item__link">{$conversation->subject}</a>
			</h2>

			<div class="discussionList__item__teaser">
				{$conversation->getTeaser()}
			</div>

			{hascontent}
				<div class="discussionList__item__labels">
					<ul class="labelList">
						{content}
							{foreach from=$conversation->getAssignedLabels() item=label}
								<li><span class="badge label{if $label->cssClassName} {$label->cssClassName}{/if}">{$label->label}</span></li>
							{/foreach}
						{/content}
					</ul>
				</div>
			{/hascontent}

			{if $conversation->getTeaserImage()}
				<div class="discussionList__item__image">
					{unsafe:$conversation->getTeaserImage()->toHtml()}
				</div>
			{/if}

			{event name='content'}
		</div>

		<div class="discussionList__item__footer">
			{if $conversation->getParticipantSummary()|count}
				{assign var='participantSummaryCount' value=$conversation->getParticipantSummary()|count}
				<ul class="conversationList__item__participants">
					{if $participantSummaryCount < $conversation->participants}
						<li class="conversationList__item__otherParticipant">
							+{#$conversation->participants-$participantSummaryCount}
						</li>
					{/if}
					{foreach from=$conversation->getParticipantSummary() item='participant'}
						<li class="conversationList__item__participant jsTooltip" title="{$participant->username}">
							{unsafe:$participant->getAvatar()->getImageTag(24)}
						</li>
					{/foreach}
				</ul>
			{/if}
			
			<div class="discussionList__item__replies">
				{icon name='comments'}
				{lang replies=$conversation->replies}wcf.conversation.replies.count{/lang}
			</div>

			{if $conversation->replies != 0 && $conversation->lastPostTime}
				<div class="discussionList__item__lastPost">
					<div class="discussionList__item__lastPost__time">
						{icon name='reply'}
						<a
							href="{link controller='Conversation' object=$conversation action='lastPost'}{/link}"
							class="discussionList__item__lastPost__link jsTooltip"
							title="{lang}wcf.conversation.gotoLastPost{/lang}"
						>
							{time time=$conversation->lastPostTime}
						</a>
					</div>
					
					<div class="discussionList__item__lastPost__author">
						{unsafe:$conversation->getLastPosterProfile()->getAvatar()->getImageTag(16)}
						<span>{unsafe:$conversation->getLastPosterProfile()->getFormattedUsername()}</span>
					</div>
				</div>
			{/if}

			{event name='footer'}
		</div>
	</article>
{/foreach}
