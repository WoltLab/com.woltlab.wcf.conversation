{if $modificationLogList|isset}
	{assign var=__modificationLogEntries value=$modificationLogList->getEntriesUntil($__modificationLogTime)}
	{foreach from=$__modificationLogEntries item=modificationLogEntry}
		<li class="marginTop jsModificationLogEntry">
			<article class="message messageCollapsed">
				<div class="messageHeader">
					<div class="box24">
						<span class="icon icon16 icon-tasks"></span>
						
						<hgroup>
							<h1>{@$modificationLogEntry}</h1>
							<h2>
								<a href="{link controller='User' id=$modificationLogEntry->userID title=$modificationLogEntry->username}{/link}" class="userLink" data-user-id="{@$modificationLogEntry->userID}">{$modificationLogEntry->username}</a>
								-
								{@$modificationLogEntry->time|time}
							</h2>
						</hgroup>
					</div>
				</div>
			</article>
		</li>
	{/foreach}
{/if}