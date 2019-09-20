{**
 * templates/controllers/tab/workflow/production.tpl
 *
* Copyright (c) 2014-2019 Simon Fraser University
* Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Production workflow stage
 *}
<script type="text/javascript">
// Attach the JS file tab handler.
$(function () {ldelim}
	$('#submissionVersions').pkpHandler(
			'$.pkp.controllers.TabHandler',
			{ldelim}
				{assign var=selectedTabIndex value=$currentSubmissionVersion - 1}
				selected: {$selectedTabIndex}
				{rdelim}
	);
	{rdelim});
</script>

{* Help tab *}
{help file="editorial-workflow/production.md" class="pkp_help_tab"}

<div id="production">
{include file="controllers/notification/inPlaceNotification.tpl" notificationId="productionNotification" requestOptions=$productionNotificationRequestOptions refreshOn="stageStatusUpdated"}
	{if $isJatsTemplatePluginEnabled}
		<div id="productionXmlUploadNotification" class="pkp_notification">
			<div class="notifyInfo">
				<span class="title"></span>
				<span class="description">
					{translate key="submission.upload.productionReadyXML.notification"}
					<div class="pkp_button">{include file="linkAction/linkAction.tpl" action=$xmlFileCreateLinkAction submissionId=$submissionId stageId=$stageId fileStage=$fileStage}</div>
					<div class="pkp_button">{include file="linkAction/linkAction.tpl" action=$xmlFileUploadLinkAction submissionId=$submissionId stageId=$stageId fileStage=$fileStage}</div>
				</span>
			</div>
		</div>
	{/if}


	<div class="pkp_context_sidebar">
		{if array_intersect(array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR), (array)$userRoles)}
			<div id="schedulePublicationDiv" class="pkp_tab_actions">
				<ul class="pkp_workflow_decisions">
					<li>{include file="linkAction/linkAction.tpl" action=$schedulePublicationLinkAction}</li>
				</ul>
			</div>
		{/if}
		{include file="controllers/tab/workflow/stageParticipants.tpl"}
	</div>

	<div class="pkp_content_panel">
		{capture assign=productionReadyFilesGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.files.productionReady.ProductionReadyFilesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}{/capture}
		{load_url_in_div id="productionReadyFilesGridDiv" url=$productionReadyFilesGridUrl}

		{capture assign=queriesGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.queries.QueriesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}{/capture}
		{load_url_in_div id="queriesGrid" url=$queriesGridUrl}

		{capture assign=representationsGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.articleGalleys.ArticleGalleyGridHandler" op="fetchGrid" submissionId=$submission->getId() escape=false}{/capture}
		{load_url_in_div id="formatsGridContainer"|uniqid url=$representationsGridUrl}
	</div>
</div>
