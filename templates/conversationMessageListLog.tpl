{if $modificationLogList|isset}
	{assign var=__modificationLogEntries value=$modificationLogList->getEntriesUntil($__modificationLogTime)}
	{foreach from=$__modificationLogEntries item=modificationLogEntry}
		<li class="jsModificationLogEntry">
			<article class="message messageReduced">
				<div class="messageContent">
					<div class="messageHeader">
						<div class="box32 messageHeaderWrapper">
							{user object=$modificationLogEntry->getUserProfile() type='avatar32' ariaHidden='true' tabindex='-1'}
							
							<div class="messageHeaderBox">
								<h2 class="messageTitle">
									{user object=$modificationLogEntry->getUserProfile() class='username'}
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
