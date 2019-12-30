{**
 * templates/controllers/tab/workflow/production.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Production workflow stage
 *}

{* Help tab *}
{help file="editorial-workflow/production" class="pkp_help_tab"}

<div id="production">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="productionNotification" requestOptions=$productionNotificationRequestOptions refreshOn="stageStatusUpdated"}

	{if $authorPublishRequirements}
		<div id="authorPublishRequirements" class="pkp_notification">
			{include file="controllers/notification/inPlaceNotificationContent.tpl" notificationId=authorPublishRequirements notificationStyleClass="notifyWarning" notificationTitle="editor.publication.authorPublishRequirements"|translate notificationContents="$authorPublishRequirements"}

		</div>
	{/if}

	<div class="pkp_context_sidebar">
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

	<div class="pkp_content_panel">
		{capture assign=queriesGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.queries.QueriesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}{/capture}
		{load_url_in_div id="queriesGrid" url=$queriesGridUrl}
	</div>

</div>
