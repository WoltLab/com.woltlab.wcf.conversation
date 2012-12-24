{include file='documentHeader'}

<head>
	<title>{lang}wcf.conversation.log{/lang} - {$conversation->subject} - {PAGE_TITLE|language}</title>
	
	{include file='headInclude'}
</head>

<body id="tpl{$templateName|ucfirst}">

{include file='header'}

<header class="boxHeadline marginTop">
	<hgroup>
		<h1><a href="{link controller='ConversationLog' id=$conversation->conversationID}{/link}">{lang}wcf.conversation.log{/lang}</a></h1>
	</hgroup>
</header>

{include file='userNotice'}

<div class="contentNavigation">
	{pages print=true assign=pagesLinks controller='ConversationLog' id=$conversation->conversationID link="pageNo=%d"}
</div>

{hascontent}
	<div class="tabularBox tabularBoxTitle marginTop">
		<hgroup>
			<h1>{lang}wcf.conversation.log.title{/lang} <span class="badge badgeInverse">{#$items}</span></h1>
		</hgroup>
		
		<table class="table">
			<thead>
				<tr>
					<th class="columnID{if $sortField == 'logID'} active{/if}"><a href="{link controller='ConversationLog' id=$conversation->conversationID}pageNo={@$pageNo}&sortField=logID&sortOrder={if $sortField == 'logID' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{/link}">{lang}wcf.global.objectID{/lang}{if $sortField == 'logID'} <img src="{icon}sort{@$sortOrder}{/icon}" alt="" />{/if}</a></th>
					<th class="columnText">{lang}wcf.conversation.log.action{/lang}</th>
					<th class="columnID{if $sortField == 'username'} active{/if}"><a href="{link controller='ConversationLog' id=$conversation->conversationID}pageNo={@$pageNo}&sortField=username&sortOrder={if $sortField == 'username' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{/link}">{lang}wcf.user.username{/lang}{if $sortField == 'username'} <img src="{icon}sort{@$sortOrder}{/icon}" alt="" />{/if}</a></th>
					<th class="columnID{if $sortField == 'time'} active{/if}"><a href="{link controller='ConversationLog' id=$conversation->conversationID}pageNo={@$pageNo}&sortField=time&sortOrder={if $sortField == 'time' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{/link}">{lang}wcf.conversation.log.time{/lang}{if $sortField == 'time'} <img src="{icon}sort{@$sortOrder}{/icon}" alt="" />{/if}</a></th>
				</tr>
			</thead>
			<tbody>
				{content}
					{foreach from=$objects item=entry}
						<tr>
							<td class="columnID"><p>{#$entry->logID}</p></td>
							<td class="columnText"><p>{@$entry}</p></td>
							<td class="columnText"><p><a href="{link controller='User' id=$entry->userID title=$entry->username}{/link}" class="userLink" data-user-id="{@$entry->userID}">{$entry->username}</a></p></td>
							<td class="columnData"><p><small>{@$entry->time|time}</small></p></td>
						</tr>
					{/foreach}
				{/content}
			</tbody>
		</table>
	</div>
{hascontentelse}
	<p class="info">{lang}wcf.conversation.log.noEntries{/lang}</p>
{/hascontent}

<div class="contentNavigation">
	{@$pagesLinks}
</div>

{include file='footer'}

</body>
</html>
