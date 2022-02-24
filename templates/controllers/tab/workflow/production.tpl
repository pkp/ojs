{**
 * templates/controllers/tab/workflow/production.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Production workflow stage
 *}

{* Help tab *}
{help file="editorial-workflow/production" class="pkp_help_tab"}

<div id="production">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="productionNotification" requestOptions=$productionNotificationRequestOptions refreshOn="stageStatusUpdated"}

	<div class="pkp_workflow_sidebar">
		{capture assign=productionEditorDecisionsUrl}{url router=\PKP\core\PKPApplication::ROUTE_PAGE page="workflow" op="editorDecisionActions" submissionId=$submission->getId() stageId=$stageId escape=false}{/capture}
		{load_url_in_div id="productionEditorDecisionsDiv" url=$productionEditorDecisionsUrl class="editorDecisionActions pkp_tab_actions"}
		{capture assign=stageParticipantGridUrl}{url router=\PKP\core\PKPApplication::ROUTE_COMPONENT component="grid.users.stageParticipant.StageParticipantGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}{/capture}
		{load_url_in_div id="stageParticipantGridContainer" url=$stageParticipantGridUrl class="pkp_participants_grid"}
	</div>

	<div class="pkp_workflow_content">
		{capture assign=productionReadyFilesGridUrl}{url router=\PKP\core\PKPApplication::ROUTE_COMPONENT component="grid.files.productionReady.ProductionReadyFilesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}{/capture}
		{load_url_in_div id="productionReadyFilesGridDiv" url=$productionReadyFilesGridUrl}
		{capture assign=queriesGridUrl}{url router=\PKP\core\PKPApplication::ROUTE_COMPONENT component="grid.queries.QueriesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}{/capture}
		{load_url_in_div id="queriesGrid" url=$queriesGridUrl}
	</div>

</div>
