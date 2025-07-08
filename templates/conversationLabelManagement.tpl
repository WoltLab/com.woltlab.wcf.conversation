{hascontent}
	<section class="section">
		<header class="sectionHeader">
			<h2 class="sectionTitle">{lang}wcf.conversation.label.management.existingLabels{/lang}</h2>
			<p class="sectionDescription">{lang}wcf.conversation.label.management.edit.description{/lang}</p>
		</header>
		<ul class="conversationLabelList">
			{content}
				{foreach from=$labelList item=label}
					<li>
						<button type="button" class="badge label{if $label->cssClassName} {$label->cssClassName}{/if}" data-label-id="{$label->labelID}" data-css-class-name="{if $label->cssClassName}{$label->cssClassName}{else}none{/if}">{$label->label}</button>
					</li>
				{/foreach}
			{/content}
		</ul>
	</section>
{/hascontent}

<section class="section">
	<button type="button" class="button addLabel buttonPrimary">{lang}wcf.conversation.label.management.addLabel{/lang}</button>
</section>
