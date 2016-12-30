{assign var='count' value=$event->getAuthors()|count}{assign var='guestTimesTriggered' value=$event->getNotification()->guestTimesTriggered}{assign var='authors' value=$event->getAuthors()|array_values}
{if $mimeType === 'text/plain'}
{capture assign='authorList'}{lang}wcf.user.notification.mail.authorList.plaintext{/lang}{/capture}
{lang}wcf.user.notification.conversation.message.mail.plaintext{/lang}{if $count == 1 && !$guestTimesTriggered}

{@$event->getUserNotificationObject()->getMailText($mimeType)}{/if} {* this line ends with a space *}
{else}
	{capture assign='authorList'}{lang}wcf.user.notification.mail.authorList.html{/lang}{/capture}
	{lang}wcf.user.notification.conversation.message.mail.html{/lang}
	{assign var='user' value=$event->getAuthor()}
	{assign var='message' value=$event->getUserNotificationObject()}
	{assign var='conversation' value=$message->getConversation()}
	
	{if $notificationType == 'instant'}{assign var='avatarSize' value=128}
	{else}{assign var='avatarSize' value=64}{/if}
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
