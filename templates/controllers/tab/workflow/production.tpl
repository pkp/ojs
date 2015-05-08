{**
 * templates/controllers/tab/workflow/production.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Production workflow stage
 *}
<script type="text/javascript">
	// Initialise JS handler.
	$(function() {ldelim}
		$('#production').pkpHandler(
			'$.pkp.pages.workflow.ProductionHandler',
			{ldelim}
				formatsTabContainerSelector: '#representationsTabsContainer',
				submissionProgressBarSelector: '#submissionProgressBarDiv'
			{rdelim}
		);
	{rdelim});
</script>
{include file="controllers/tab/workflow/stageParticipants.tpl"}

<div id="production">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="productionNotification" requestOptions=$productionNotificationRequestOptions}

	<p class="pkp_help">{translate key="editor.submission.production.introduction"}</p>

	{url|assign:productionReadyFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.productionReady.ProductionReadyFilesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}
	{load_url_in_div id="productionReadyFilesGridDiv" url=$productionReadyFilesGridUrl}

	{if array_intersect(array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR), $userRoles)}
		{fbvFormArea id="representations"}
			{fbvFormSection}
				<!--  Representations -->
				{url|assign:representationsGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.articleGalleys.ArticleGalleyGridHandler" op="fetchGrid" submissionId=$submission->getId()}
				{load_url_in_div id="formatsGridContainer"|uniqid url=$representationsGridUrl}
			{/fbvFormSection}
		{/fbvFormArea}
	{else}
		<h3>{translate key="submission.galleys"}</h3>
	{/if}

	<div id="representationsTabsContainer">
		{include file="controllers/tab/workflow/galleysTab.tpl" galleyTabsId=$galleyTabsId representations=$representations}
	</div>
</div>
