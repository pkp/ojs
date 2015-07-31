{**
 * templates/frontend/objects/issue_toc.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief View of an Issue which displays a full table of contents.
 *
 * @uses $issue Issue The issue
 * @uses $issueTitle string Title of the issue. May be empty
 * @uses $issueSeries string Vol/No/Year string for the issue
 * @uses $galleys IssueGalleys Galleys for the entire issue
 * @uses $hasAccess bool Can this user access galleys for this context?
 * @uses $showGalleyLinks bool Show galley links to users without access?
 *}
<div class="obj_issue_toc">

	{* Indicate if this is only a preview *}
	{if !$issue->getPublished()}
		{include file="frontend/components/notification.tpl" type="warning" message={translate key="editor.issues.preview"}}
	{/if}

	{* Title *}
	<h1 class="title">
		{if $issueTitle}
			{$issueTitle}
		{else}
			{$issueSeries}
		{/if}
	</h1>
	{if $issueTitle}
		<h2 class="series">
			{$issueSeries}
		</h2>
	{/if}

	{* Description *}
	{if $issue->hasDescription()}
		<div class="description">
			{$issue->getLocalizedDescription()|strip_unsafe_html|nl2br}
		</div>
	{/if}

	{* Full-issue galleys *}
	{if $issueGalleys && ($hasAccess || $showGalleyLinks)}
		<h3 class="heading_full_issue">
			{translate key="issue.fullIssue"}
		</h3>
		<ul class="galleys_links">
			{foreach from=$issueGalleys item=galley}
				<li>
					{include file="frontend/objects/galley_link.tpl" parent=$issue}
				</li>
			{/foreach}
		</ul>
	{/if}

	{* Articles *}
	<ul class="sections">
	{foreach name=sections from=$publishedArticles item=section}
		{if $section.articles}
			<li class="section section_{$section.title|escape|replace:' ':'_'}">
				{if $section.title}
					<h3>
						{$section.title}
					</h3>
				{/if}

				<ul class="articles">
					{foreach from=$section.articles item=article}
						<li>
							{include file="frontend/objects/article_summary.tpl"}
						</li>
					{/foreach}
				</ul>
			</li>
		{/if}
	{/foreach}
	</ul>

	{* Display a legend describing the open/restricted access icons *}
	{if $showGalleyLinks && $showToc}
		{include file="frontend/components/accessLegend.tpl"}
	{/if}
</div>
