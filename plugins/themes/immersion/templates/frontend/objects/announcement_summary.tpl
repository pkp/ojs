{**
 * templates/frontend/objects/announcement_summary.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Display a summary view of an announcement
 *
 * @uses $announcement Announcement The announcement to display
 * @uses $heading string HTML heading element, default: h2
 *}
{if !$heading}
	{assign var="heading" value="h2"}
{/if}

<article>
	<{$heading}>
		<a href="{url router=$smarty.const.ROUTE_PAGE page="announcement" op="view" path=$announcement->getId()}">
			{$announcement->getLocalizedTitle()|escape}
		</a>
	</{$heading}>

	<p class="text-muted">
		{$announcement->getDatePosted()|date_format:$dateFormatShort}
	</p>

	<div class="announcements__short-description">
		{$announcement->getLocalizedDescriptionShort()|strip_unsafe_html}
	</div>

	<a href="{url router=$smarty.const.ROUTE_PAGE page="announcement" op="view" path=$announcement->getId()}" class="btn btn-secondary">
		<span aria-hidden="true" role="presentation">
			{translate key="common.readMore"}
		</span>
		<span class="visually-hidden">
			{translate key="common.readMoreWithTitle" title=$announcement->getLocalizedTitle()|escape}
		</span>
	</a>
</article>
