{**
 * templates/controllers/tab/workflow/production.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Production workflow stage
 *}

<div id="production">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="productionNotification" requestOptions=$productionNotificationRequestOptions}

	<div class="pkp_context_sidebar">
		{include file="controllers/tab/workflow/stageParticipants.tpl"}
	</div>

	<div class="pkp_content_panel">

		<p class="pkp_help">{translate key="editor.submission.production.introduction"}</p>

		{url|assign:productionReadyFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.productionReady.ProductionReadyFilesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}
		{load_url_in_div id="productionReadyFilesGridDiv" url=$productionReadyFilesGridUrl}

		{url|assign:queriesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.queries.QueriesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}
		{load_url_in_div id="queriesGridDiv" url=$queriesGridUrl}

		{url|assign:representationsGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.articleGalleys.ArticleGalleyGridHandler" op="fetchGrid" submissionId=$submission->getId() escape=false}
		{load_url_in_div id="formatsGridContainer"|uniqid url=$representationsGridUrl}
	</div>
</div>
