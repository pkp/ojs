{**
 * templates/authorDashboard/authorDashboard.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the author dashboard.
 *}
{strip}
{assign var=primaryAuthor value=$submission->getPrimaryAuthor()}
{if !$primaryAuthor}
	{assign var=authors value=$submission->getAuthors()}
	{assign var=primaryAuthor value=$authors[0]}
{/if}
{assign var="pageTitleTranslated" value=$primaryAuthor->getLastName()|concat:", <em>":$submission->getLocalizedTitle():"</em>"|truncate:50}
{include file="common/header.tpl" suppressPageTitle=true}
{/strip}

{assign var="stageId" value=$submission->getStageId()}

<script type="text/javascript">
	// Initialise JS handler.
	$(function() {ldelim}
		$('#authorDashboard').pkpHandler(
				'$.pkp.pages.authorDashboard.PKPAuthorDashboardHandler',
				{ldelim} currentStage: {$stageId} {rdelim});
	{rdelim});
</script>

<div id="authorDashboard">
	{include file="authorDashboard/top.tpl"}

	{include file="authorDashboard/stages/submission.tpl"}
	{include file="authorDashboard/stages/externalReview.tpl"}
	{include file="authorDashboard/stages/editorial.tpl"}
	{include file="authorDashboard/stages/production.tpl"}

	{include file="authorDashboard/submissionDocuments.tpl"}
</div>

{include file="common/footer.tpl"}
