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
<div class="obj_issue_summary">

	{* Retrieve separate entries for $issueTitle and $issueSeries *}
	{assign var=issueTitle value=$issue->getLocalizedTitle()}
	{assign var=issueSeries value=$issue->getIssueSeries()}

	{* Show cover image and use cover description *}
	{if $issue->getLocalizedFileName() && $issue->getShowCoverPage($currentLocale) && !$issue->getHideCoverPageArchives($currentLocale)}
		<a class="cover" href="{url op="view" path=$issue->getBestIssueId($currentJournal)}">
			<img src="{$coverPagePath|escape}{$issue->getFileName($currentLocale)|escape}"{if $issue->getCoverPageAltText($currentLocale) != ''} alt="{$issue->getCoverPageAltText($currentLocale)|escape}"{else} alt="{translate key="issue.coverPage.altText"}"{/if}/>
		</a>
		{assign var="issueDescription" value=$issue->getLocalizedCoverPageDescription()}
	{else}
		{assign var="issueDescription" value=$issue->getLocalizedDescription()}
	{/if}

	<a class="title" href="{url op="view" path=$issue->getBestIssueId($currentJournal)}">
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
		{$issueDescription|strip_unsafe_html|nl2br}
	</div>
</div><!-- .obj_issue_summary -->
