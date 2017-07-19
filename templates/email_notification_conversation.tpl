{if $mimeType === 'text/plain'}
{lang}wcf.user.notification.conversation.mail.plaintext{/lang}

{@$event->getUserNotificationObject()->getFirstMessage()->getMailText($mimeType)} {* this line ends with a space *}
{else}
	{lang}wcf.user.notification.conversation.mail.html{/lang}
	{assign var='user' value=$event->getAuthor()}
	{assign var='conversation' value=$event->getUserNotificationObject()}
	{assign var='message' value=$conversation->getFirstMessage()}
	
	{if $notificationType == 'instant'}{assign var='avatarSize' value=48}
	{else}{assign var='avatarSize' value=32}{/if}
	{capture assign='messageContent'}
	<table cellpadding="0" cellspacing="0" border="0">
		<tr>
			<td><a href="{link controller='User' object=$user isEmail=true}{/link}" title="{$message->username}">{@$user->getAvatar()->getImageTag($avatarSize)}</a></td>
			<td class="boxContent">
				<div class="containerHeadline">
					<h3>
						{if $message->userID}
							<a href="{link controller='User' object=$user isEmail=true}{/link}">{$message->username}</a>
						{else}
							{$message->username}
						{/if}
						&#xb7;
						<a href="{$message->getLink()}"><small>{$message->time|plainTime}</small></a>
					</h3>
				</div>
				<div>
					{@$message->getMailText($mimeType)}
				</div>
			</td>
		</tr>
	</table>
	{/capture}
	{include file='email_paddingHelper' block=true class='box'|concat:$avatarSize content=$messageContent sandbox=true}
{/if}
