<div class="box48">
	{if $message->getUserProfile()->getAvatar()}
		{user object=$message->getUserProfile() type='avatar48' ariaHidden='true' tabindex='-1'}
	{/if}
	
	<div>
		<div class="containerHeadline">
			<h3>{user object=$message->getUserProfile()} <small class="separatorLeft">{time time=$message->time}</small></h3>
		</div>
		
		<div>{@$message->getExcerpt()}</div>
		
		{event name='previewData'}
	</div>
</div>
