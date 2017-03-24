{**
 * templates/workflow/version.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Version view for workflow stage production
 *}
{if !$isPublished}
	<div class="pkp_notification">
		<div class="notifyInfo">
			{translate key="submission.versioning.notPublished"}
		</div>
	</div>
{/if}
<div class="pkp_panel_wrapper">

	<div class="pkp_context_sidebar">

		{** edit metadata of this version **}
		<div id="editMetadataDiv" class="pkp_tab_actions">
			<ul class="pkp_workflow_decisions">
				<li>{include file="linkAction/linkAction.tpl" action=$editMetadataLinkAction}</li>
			</ul>
		</div>
		
		{** schedule for publication **}
		{if $schedulePublicationLinkAction and array_intersect(array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR), (array)$userRoles)}
			<div id="schedulePublicationDiv" class="pkp_tab_actions">
				<ul class="pkp_workflow_decisions">
					<li>{include file="linkAction/linkAction.tpl" action=$schedulePublicationLinkAction}</li>
				</ul>
			</div>
		{/if}

		{** stage participants **}
		{url|assign:stageParticipantGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.users.stageParticipant.StageParticipantGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}
		{load_url_in_div id="stageParticipantGridContainer-version_"|concat:$submissionRevision url=$stageParticipantGridUrl class="pkp_participants_grid"}

	</div>

	<div class="pkp_content_panel">

		{** production ready files **}
		{url|assign:productionReadyFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.productionReady.ProductionReadyFilesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}
		{load_url_in_div id="productionReadyFilesGridDiv-version_"|concat:$submissionRevision url=$productionReadyFilesGridUrl}

		{** galleys **}
		{url|assign:representationsGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.articleGalleys.ArticleGalleyGridHandler" op="fetchGrid" submissionId=$submission->getId() submissionRevision=$submissionRevision escape=false}
		{load_url_in_div id="formatsGridContainer-version_"|concat:$submissionRevision url=$representationsGridUrl}

	</div>
</div>
