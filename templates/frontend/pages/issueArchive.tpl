{**
 * templates/frontend/pages/issueArchive.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Display a list of recent issues.
 *
 * @uses $issues Array Collection of issues to display
 *}
{if $issues->getPageCount() > 0 && $issues->getPage() > 1}
	{assign var=pageNumber value={translate key="common.pageNumber" pageNumber=$issues->getPage()}}
{/if}
{include file="common/frontend/header.tpl" pageTitle="archive.archives"}

<div class="page">

	<h1 class="page_title">
		{translate key="archive.archives"}
		{if $pageNumber}
			{translate key="common.pageNumber"}
		{/if}
	</h1>

	{* No issues have been published *}
	{if !$issues}
		{translate key="current.noCurrentIssueDesc"}

	{* List issues *}
	{else}
		<ul class="issues_archive">
			{iterate from=issues item=issue}
				<li>
					{include file="frontend/objects/issue_summary.tpl"}
				</li>
			{/iterate}
		</ul>

		{if $issues->getPageCount() > 0}
			<div class="cmp_pagination">
				{page_info iterator=$issues}
				{page_links anchor="issues" name="issues" iterator=$issues}
			</div>
		{/if}
	{/if}
</div>

{include file="common/frontend/footer.tpl"}
