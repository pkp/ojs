{**
 * templates/workflow/production.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Production workflow stage
 *}
{strip}
{include file="workflow/header.tpl"}
{/strip}

<div id="production">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="productionNotification" requestOptions=$productionNotificationRequestOptions}

	<p class="pkp_help">{translate key="editor.submission.production.introduction"}</p>

	{url|assign:productionReadyFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.productionReady.ProductionReadyFilesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}
	{load_url_in_div id="productionReadyFilesGridDiv" url=$productionReadyFilesGridUrl}

	{if array_intersect(array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR), $userRoles)}
		{fbvFormArea id="galleys"}
			{fbvFormSection}
				<!--  Galleys -->
				{url|assign:galleyGridUrl router=$smarty.const.ROUTE_COMPONENT  component="grid.articleGalleys.ArticleGalleyGridHandler" op="fetchGrid" submissionId=$submission->getId()}
				{load_url_in_div id="galleysGridContainer"|uniqid url=$galleyGridUrl}
			{/fbvFormSection}
		{/fbvFormArea}
	{else}
		<h3>{translate key="submission.galleys"}</h3>
	{/if}

	<div id='galleyTabsContainer'>
		{include file="workflow/galleysTab.tpl" galleyTabsId=$galleyTabsId galleys=$galleys}
	</div>
</div>

{include file="common/footer.tpl"}
