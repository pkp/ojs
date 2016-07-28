{**
 * templates/frontend/objects/issue_summary.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief View of an Issue which displays a summary for use in lists
 *
 * @uses $issue Issue The issue
 *}
{assign var=issueTitle value=$issue->getLocalizedTitle()}
{assign var=issueSeries value=$issue->getIssueSeries()}
{assign var=issueCover value=$issue->getLocalizedFileName()}

<div class="obj_issue_summary">

	{if $issueCover}
		<a class="cover" href="{url op="view" path=$issue->getBestIssueId()}">
			<img src="{$coverPagePath|escape}{$issueCover|escape}"{if $issue->getCoverPageAltText($currentLocale) != ''} alt="{$issue->getCoverPageAltText($currentLocale)|escape}"{/if}>
		</a>
	{/if}

	<a class="title" href="{url op="view" path=$issue->getBestIssueId()}">
		{if $issueTitle}
			{$issueTitle|escape}
		{else}
			{$issueSeries|escape}
		{/if}
	</a>
	{if $issueTitle}
		<div class="series">
			{$issueSeries|escape}
		</div>
	{/if}

	<div class="description">
		{$issue->getLocalizedDescription()|strip_unsafe_html}
	</div>
</div><!-- .obj_issue_summary -->
