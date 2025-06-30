{foreach from=$view->getItems() item=conversation}
	<div class="listView__item tabularListRow" data-object-id="{$conversation->getObjectID()}">
		<ol class="tabularListColumns messageGroup conversation{if $conversation->isNew()} new{/if}" data-conversation-id="{$conversation->conversationID}">
			<li class="columnInteractions">
				{if $view->hasBulkInteractions()}
					<label class="button small jsTooltip" title="{lang}wcf.clipboard.item.mark{/lang}">
						<input type="checkbox" class="listView__selectItem" aria-label="{lang}wcf.clipboard.item.mark{/lang}">
					</label>
				{/if}

				{unsafe:$view->renderInteractionContextMenuButton($conversation)}
			</li>
			<li class="columnIcon columnAvatar conversationList_columnAvatar">
				{if $conversation->getUserProfile()->getAvatar()}
					<div>
						<p{if $conversation->isNew()} title="{lang}wcf.conversation.markAsRead.doubleClick{/lang}"{/if}>{unsafe:$conversation->getUserProfile()->getAvatar()->getImageTag(48)}</p>

						{if $conversation->ownPosts && $conversation->userID != $__wcf->user->userID}
							{if $__wcf->getUserProfileHandler()->getAvatar()}
								<small class="myAvatar jsTooltip" title="{lang}wcf.conversation.ownPosts{/lang}">{unsafe:$__wcf->getUserProfileHandler()->getAvatar()->getImageTag(24)}</small>
							{/if}
						{/if}
					</div>
				{/if}
			</li>
			<li class="columnSubject conversationList_columnSubject">
				{hascontent}
					<ul class="labelList">
						{content}
							{foreach from=$conversation->getAssignedLabels() item=label}
								<li><span class="badge label{if $label->cssClassName} {$label->cssClassName}{/if}">{$label->label}</span></li>
							{/foreach}
						{/content}
					</ul>
				{/hascontent}

				<h3>
					<a href="{if $conversation->isNew()}{link controller='Conversation' object=$conversation action='firstNew'}{/link}{else}{$conversation->getLink()}{/if}" class="conversationLink messageGroupLink" data-object-id="{$conversation->conversationID}">{$conversation->subject}</a>
					{if $conversation->replies}
						<span class="badge messageGroupCounterMobile">{$conversation->replies|shortUnit}</span>
					{/if}
				</h3>

				<aside class="statusDisplay" role="presentation">
					<ul class="statusIcons">
						{if $conversation->isClosed}
							<li>
								<span class="jsIconLock jsTooltip" title="{lang}wcf.global.state.closed{/lang}">
									{icon name='lock'}
								</span>
							</li>
						{/if}
						{if $conversation->attachments}
							<li>
								<span class="jsIconAttachment jsTooltip" title="{lang}wcf.conversation.attachments{/lang}">
									{icon name='paperclip'}
								</span>
							</li>
						{/if}
					</ul>
				</aside>

				<ul class="inlineList dotSeparated small messageGroupInfo">
					<li class="messageGroupAuthor">{user object=$conversation->getUserProfile()}</li>
					<li class="messageGroupTime">{time time=$conversation->time}</li>
					{event name='messageGroupInfo'}
				</ul>

				<ul class="messageGroupInfoMobile">
					<li class="messageGroupAuthorMobile">{$conversation->username}</li>
					<li class="messageGroupLastPostTimeMobile">{time time=$conversation->lastPostTime}</li>
				</ul>

				{if $conversation->getParticipantSummary()|count}
					<small class="conversationParticipantSummary">
						{assign var='participantSummaryCount' value=$conversation->getParticipantSummary()|count}
						{lang}wcf.conversation.participants{/lang}: {implode from=$conversation->getParticipantSummary() item=participant}<a href="{$participant->getLink()}" class="userLink{if $participant->hideConversation == 2} conversationLeft{/if}" data-object-id="{$participant->userID}">{$participant->username}</a>{/implode}
						{if $participantSummaryCount < $conversation->participants}{lang}wcf.conversation.participants.other{/lang}{/if}
					</small>
				{/if}

				{event name='conversationData'}
			</li>
			<li class="columnStats">
				<dl class="plain statsDataList">
					<dt>{lang}wcf.conversation.replies{/lang}</dt>
					<dd>{$conversation->replies|shortUnit}</dd>
				</dl>
				<dl class="plain statsDataList">
					<dt>{lang}wcf.conversation.participants{/lang}</dt>
					<dd>{$conversation->participants|shortUnit}</dd>
				</dl>

				<div class="messageGroupListStatsSimple">
					{if $conversation->replies}
						<span aria-label="{lang}wcf.conversation.replies{/lang}">
							{icon name='comment'}
						</span>
						{$conversation->replies|shortUnit}
					{/if}
				</div>
			</li>
			<li class="columnLastPost">
				{if $conversation->replies != 0 && $conversation->lastPostTime}
					<div class="box32">
						<a href="{link controller='Conversation' object=$conversation action="lastPost"}{/link}" class="jsTooltip" title="{lang}wcf.conversation.gotoLastPost{/lang}">{unsafe:$conversation->getLastPosterProfile()->getAvatar()->getImageTag(32)}</a>

						<div>
							<p>
								{user object=$conversation->getLastPosterProfile()}
							</p>
							<small>{time time=$conversation->lastPostTime}</small>
						</div>
					</div>
				{/if}
			</li>

			{event name='columns'}
		</ol>
	</div>
{/foreach}
