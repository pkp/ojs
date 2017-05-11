{**
 * templates/workflow/reviewRound.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Review round info for a particular round
 *}
<div class="pkp_panel_wrapper">

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="reviewRoundNotification_"|concat:$reviewRoundId requestOptions=$reviewRoundNotificationRequestOptions}

	{* Editorial decision actions, if available *}
	<div class="pkp_context_sidebar">
		{url|assign:reviewDecisionsUrl router=$smarty.const.ROUTE_PAGE page="workflow" op="editorDecisionActions" submissionId=$submission->getId() stageId=$stageId reviewRoundId=$reviewRoundId contextId="reviewRoundTab-"|concat:$reviewRoundId escape=false}
		{load_url_in_div id="reviewDecisionsDiv-"|concat:$reviewRoundId url=$reviewDecisionsUrl class="pkp_tab_actions"}
		{include file="controllers/tab/workflow/stageParticipants.tpl"}
	</div>

	<div class="pkp_content_panel">

		{* Review files grid *}
		{url|assign:reviewFileSelectionGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.review.EditorReviewFilesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId reviewRoundId=$reviewRoundId escape=false}
		{load_url_in_div id="reviewFileSelection-round_"|concat:$reviewRoundId url=$reviewFileSelectionGridUrl}

		{* Reviewer grid *}
		{url|assign:reviewersGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.users.reviewer.ReviewerGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId reviewRoundId=$reviewRoundId escape=false}
		{load_url_in_div id="reviewersGrid-round_"|concat:$reviewRoundId url=$reviewersGridUrl}

		{* Review revisions grid *}
		{url|assign:revisionsGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.review.WorkflowReviewRevisionsGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId reviewRoundId=$reviewRoundId escape=false}
		{load_url_in_div id="revisionsGrid-round_"|concat:$reviewRoundId url=$revisionsGridUrl}
	</div>
</div>
