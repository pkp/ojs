{**
 * templates/issue/archive.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Present a list of published issues in the journal's archive.
 *
 * Available data:
 *  $issues: ItemIterator of issues in the journal's archive.
 *}
{include file="common/frontend/header.tpl" pageTitle="archive.archives"}

<div class="issue_archive">

	{if $issues}
		<ul class="ojs_issues">
			{iterate from=issues item=issue}

				<li>

					{* Show cover image *}
					{if $issue->getLocalizedFileName() && $issue->getShowCoverPage($currentLocale) && !$issue->getHideCoverPageArchives($currentLocale)}
						<a class="cover" href="{url op="view" path=$issue->getBestIssueId($currentJournal)}">
							<img src="{$coverPagePath|escape}{$issue->getFileName($currentLocale)|escape}"{if $issue->getCoverPageAltText($currentLocale) != ''} alt="{$issue->getCoverPageAltText($currentLocale)|escape}"{else} alt="{translate key="issue.coverPage.altText"}"{/if}/>
						</a>
						{assign var="issueDescription" value=$issue->getLocalizedCoverPageDescription()}
					{else}
						{assign var="issueDescription" value=$issue->getLocalizedDescription()}
					{/if}

					<a class="title" href="{url op="view" path=$issue->getBestIssueId($currentJournal)}">
						{$issue->getIssueIdentification()|escape}
					</a>
					<div class="description">
						{$issueDescription|strip_unsafe_html|nl2br}
					</div>
				</li>
			{/iterate}
		</ul>

		{if $issues->getPageCount() > 0}
			<div class="ojs_pagination">
				{page_info iterator=$issues}
				{page_links anchor="issues" name="issues" iterator=$issues}
			</div>
		{/if}

	{else}
		{translate key="current.noCurrentIssueDesc"}
	{/if}
</div>

{include file="common/frontend/footer.tpl"}
