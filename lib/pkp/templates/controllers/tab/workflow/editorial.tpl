{**
 * templates/workflow/editorial.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Editorial workflow stage
 *}
<div id="editorial">

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="editingNotification_"|concat:$submission->getId() requestOptions=$editingNotificationRequestOptions}

	{* Help Link *}
	{help file="editorial-workflow/copyediting.md" class="pkp_help_tab"}

	<div class="pkp_context_sidebar">
		{url|assign:copyeditingEditorDecisionsUrl router=$smarty.const.ROUTE_PAGE page="workflow" op="editorDecisionActions" submissionId=$submission->getId() stageId=$stageId escape=false}
		{load_url_in_div id="copyeditingEditorDecisionsDiv" url=$copyeditingEditorDecisionsUrl class="editorDecisionActions pkp_tab_actions"}
		{include file="controllers/tab/workflow/stageParticipants.tpl"}
	</div>

	<div class="pkp_content_panel">
		{url|assign:finalDraftFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.final.FinalDraftFilesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}
		{load_url_in_div id="finalDraftFilesGrid" url=$finalDraftFilesGridUrl}

		{url|assign:queriesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.queries.QueriesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}
		{load_url_in_div id="queriesGrid" url=$queriesGridUrl}

		{url|assign:copyeditedFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.copyedit.CopyeditFilesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}
		{load_url_in_div id="copyeditedFilesGrid" url=$copyeditedFilesGridUrl}
	</div>

</div>
