{**
 * templates/frontend/objects/issue_toc.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief View of an Issue which displays a full table of contents.
 *
 * @uses $issue Issue The issue
 * @uses $issueTitle string Title of the issue. May be empty
 * @uses $issueSeries string Vol/No/Year string for the issue
 * @uses $issueGalleys array Galleys for the entire issue
 * @uses $hasAccess bool Can this user access galleys for this context?
 * @uses $publishedSubmissions array Lists of articles published in this issue
 *   sorted by section.
 * @uses $primaryGenreIds array List of file genre ids for primary file types
 * @uses $sectionHeading string Tag to use (h2, h3, etc) for section headings
 *}

<div class="container">
	<header class="issue__header">
		{if $requestedOp === "index"}
			<p class="issue__meta">{translate key="journal.currentIssue"}</p>
		{/if}
		{strip}
		{capture name="issueMetadata"}
			{if $issue->getShowVolume() || $issue->getShowNumber()}
				{if $issue->getShowVolume()}
					<span class="issue__volume">{translate key="issue.volume"} {$issue->getVolume()|escape}{if $issue->getShowNumber()}, {/if}</span>
				{/if}
				{if $issue->getShowNumber()}
					<span class="issue__number">{translate key="issue.no"} {$issue->getNumber()|escape}</span>
				{/if}
			{/if}
			{if $issue->getShowTitle()}
				<span class="issue__localized_name">{$issue->getLocalizedTitle()|escape}</span>
			{/if}
		{/capture}

		{if $requestedPage === "issue" && $smarty.capture.issueMetadata|trim !== ""}
			<h1 class="issue__title">
				{$smarty.capture.issueMetadata}
			</h1>
		{elseif $smarty.capture.issueMetadata|trim !== ""}
			<h2 class="issue__title">
            	{$smarty.capture.issueMetadata}
			</h2>
		{/if}

		{if $issue->getDatePublished()}
			<p class="issue__meta">{translate key="plugins.themes.immersion.issue.published"} {$issue->getDatePublished()|date_format:$dateFormatLong}</p>
		{/if}
		{/strip}
	</header>

	{if $issue->getLocalizedDescription() || $issueGalleys}
		<section class="row issue-desc">
			{assign var=issueCover value=$issue->getLocalizedCoverImageUrl()}
			{if $issueCover}
				<a class="col-md-2" href="{url op="view" page="issue" path=$issue->getBestIssueId()}">
					<img src="{$issueCover|escape}"{if $issue->getLocalizedCoverImageAltText() != ''} alt="{$issue->getLocalizedCoverImageAltText()|escape}"{/if} class="img-fluid">
				</a>
			{/if}
			{if $issue->getLocalizedDescription()}
				<div class="col-md-6">
					<h3 class="issue-desc__title">{translate key="plugins.themes.immersion.issue.description"}</h3>
					<div class="issue-desc__content">
						{assign var=stringLenght value=280}
						{assign var=issueDescription value=$issue->getLocalizedDescription()|strip_unsafe_html}
						{if $issueDescription|strlen <= $stringLenght || $requestedPage == 'issue'}
							{$issueDescription}
						{else}
							{$issueDescription|substr:0:$stringLenght|mb_convert_encoding:'UTF-8'|replace:'?':''|trim}
							<span class="ellipsis">...</span>
							<a class="full-issue__link"
							   href="{url op="view" page="issue" path=$issue->getBestIssueId()}">{translate key="plugins.themes.immersion.issue.fullIssueLink"}</a>
						{/if}
					</div>
				</div>
			{/if}
			{if $issueGalleys}
			<div class="col-md-6">
				{* Full-issue galleys *}
				<div class="issue-desc__galleys">
					<h3>
						{translate key="issue.fullIssue"}
					</h3>
					<ul class="issue-desc__btn-group">
						{foreach from=$issueGalleys item=galley}
							<li>
								{include file="frontend/objects/galley_link.tpl" parent=$issue purchaseFee=$currentJournal->getSetting('purchaseIssueFee') purchaseCurrency=$currentJournal->getSetting('currency')}
							</li>
						{/foreach}
					</ul>
				</div>
			</div>
			{/if}
		</section>
	{/if}
</div>

{foreach from=$publishedSubmissions item=section}
	{if $section.articles}
		{assign var='immersionColorPick' value=$section.sectionColor|escape}
		{assign var='isSectionDark' value=$section.isSectionDark}
		<section class="issue-section{if $isSectionDark} section_dark{/if}"{if $immersionColorPick} style="background-color: {$immersionColorPick};"{/if}>
			<div class="container">
				{if $section.title || $section.sectionDescription}
					<header class="row issue-section__header">
						{if $section.title}
							<h3 class="col-md-6 col-lg-3 issue-section__title">{$section.title|escape}</h3>
						{/if}
						{if $section.sectionDescription}
							<div class="col-md-6 col-lg-9 issue-section__desc">
								{$section.sectionDescription|strip_unsafe_html}
							</div>
						{/if}
					</header>
				{/if}
				<div class="row">
					<div class="col-12">
						<ol class="issue-section__toc">
							{foreach from=$section.articles item=article key=articleNumber}
								<li class="issue-section__toc-item">
									{include file="frontend/objects/article_summary.tpl"}
								</li>
							{/foreach}
						</ol>
					</div>
				</div>
			</div>
		</section>
	{/if}
{/foreach}
