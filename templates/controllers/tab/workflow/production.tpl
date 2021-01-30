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
		<div class="pkp_tab_actions">
			<ul class="pkp_workflow_decisions">
				<li>
					<button
						class="pkpButton pkpButton--isPrimary"
						onClick="pkp.eventBus.$emit('open-tab', 'publication')"
					>
						{translate key="editor.submission.schedulePublication"}
					</button>
				</li>
			</ul>
		</div>
		{capture assign=stageParticipantGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.users.stageParticipant.StageParticipantGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}{/capture}
		{load_url_in_div id="stageParticipantGridContainer" url=$stageParticipantGridUrl class="pkp_participants_grid"}
	</div>

	<div class="pkp_workflow_content">
		{capture assign=productionReadyFilesGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.files.productionReady.ProductionReadyFilesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}{/capture}
		{load_url_in_div id="productionReadyFilesGridDiv" url=$productionReadyFilesGridUrl}
		{capture assign=queriesGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.queries.QueriesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}{/capture}
		{load_url_in_div id="queriesGrid" url=$queriesGridUrl}
	</div>

</div>
