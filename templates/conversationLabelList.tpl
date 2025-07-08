{capture assign='contentInteractionButtons'}
{/capture}

{capture assign='contentHeader'}
	<header class="contentHeader">
		<div class="contentHeaderTitle">
			<h1 class="contentTitle">{$__wcf->getActivePage()->getTitle()}</h1>
		</div>

		<nav class="contentHeaderNavigation">
			<ul>
				<li>
					<button type="button" id="addLabel" class="button buttonPrimary">{icon name='plus'} <span>{lang}wcf.conversation.label.management.addLabel{/lang}</span></button>
				</li>
				{event name='contentHeaderNavigation'}
			</ul>
		</nav>
	</header>
{/capture}

{include file='header'}

<div class="section">
	{unsafe:$gridView->render()}
</div>

<script data-relocate="true">
	require(['WoltLabSuite/Core/Conversation/Component/Label/Manager'], ({ LabelManager }) => {
		new LabelManager('{link controller="ConversationLabelForm"}{/link}');
	});
</script>

{include file='footer'}
