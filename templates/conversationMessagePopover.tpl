<div class="popover__layout">
	<div class="popover__header">
		{event name='beforeHeader'}
		
		<div class="popover__avatar">
			{user object=$message->getUserProfile() type='avatar48' ariaHidden='true' tabindex='-1'}
		</div>
		<div class="popover__title">
			{user object=$message->getUserProfile()}
		</div>
		<div class="popover__time">
			{time time=$message->time}
		</div>

		{event name='afterHeader'}
	</div>

	{event name='beforeText'}

	<div class="popover__text htmlContent">
		{unsafe:$message->getExcerpt()}
	</div>

	{event name='afterText'}
</div>
