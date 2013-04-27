{if $modificationLogList|isset}
	{assign var=__modificationLogEntries value=$modificationLogList->getEntriesUntil($__modificationLogTime)}
	{foreach from=$__modificationLogEntries item=modificationLogEntry}
		<li class="marginTop jsModificationLogEntry">
			<article class="message messageCollapsed">
				<div class="messageHeader">
					<div class="box24">
						<a href="{link controller='User' object=$modificationLogEntry->getUserProfile()}{/link}" class="framed">{@$modificationLogEntry->getUserProfile()->getAvatar()->getImageTag(24)}</a>
						
						<div>
							<h1><a href="{link controller='User' object=$modificationLogEntry->getUserProfile()}{/link}" class="userLink" data-user-id="{@$modificationLogEntry->userID}">{$modificationLogEntry->username}</a>
								-
								{@$modificationLogEntry->time|time}</h1>
							<small>{@$modificationLogEntry}</small>
						</div>
					</div>
				</div>
			</article>
		</li>
	{/foreach}
{/if}