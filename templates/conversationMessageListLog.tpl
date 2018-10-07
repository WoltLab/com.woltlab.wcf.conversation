{if $modificationLogList|isset}
	{assign var=__modificationLogEntries value=$modificationLogList->getEntriesUntil($__modificationLogTime)}
	{foreach from=$__modificationLogEntries item=modificationLogEntry}
		<li class="jsModificationLogEntry">
			<article class="message messageReduced">
				<div class="messageContent">
					<div class="messageHeader">
						<div class="box32 messageHeaderWrapper">
							<a href="{link controller='User' object=$modificationLogEntry->getUserProfile()}{/link}" aria-hidden="true">{@$modificationLogEntry->getUserProfile()->getAvatar()->getImageTag(32)}</a>
							
							<div class="messageHeaderBox">
								<h2 class="messageTitle">
									<a href="{link controller='User' object=$modificationLogEntry->getUserProfile()}{/link}" class="userLink username" data-user-id="{@$modificationLogEntry->userID}">{$modificationLogEntry->username}</a>
									<small class="separatorLeft">{@$modificationLogEntry->time|time}</small>
								</h2>
								<div>{@$modificationLogEntry}</div>
							</div>
						</div>
					</div>
				</div>
			</article>
		</li>
	{/foreach}
{/if}
