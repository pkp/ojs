{**
 * templates/controllers/tab/workflow/production.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Production workflow stage
 *}

{* Help tab *}
{help file="editorial-workflow/production.md" class="pkp_help_tab"}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#submissionRevisions').pkpHandler(
			'$.pkp.controllers.TabHandler',
			{ldelim}
				{assign var=versionIndex value=$latestSubmissionRevision-1}
				selected: {$versionIndex},
				disabled: [{$submissionRevision}]
			{rdelim}
		);
	{rdelim});
</script>
<div id="production">
	{if $versioningEnabled || $submissionRevisions|@count > 1}
		<div id="submissionRevisions" class="pkp_controllers_tab">
			<ul>
				{foreach from=$submissionRevisions item=submissionRevision}
					<li>
						<a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.workflow.VersioningTabHandler" op="versioning" submissionId=$submission->getId() stageId=$stageId submissionRevision=$submissionRevision}">{translate key="submission.production.version" submissionRevision=$submissionRevision}</a>
					</li>
				{/foreach}
				{if $versioningEnabled && $newVersionAction}
					<li>
						{include file="linkAction/linkAction.tpl" image="add_item" action=$newVersionAction contextId="newVersionTabContainer"}
					</li>
				{/if}
			</ul>
		</div>

	{** production discussions **}
	{url|assign:queriesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.queries.QueriesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}
	{load_url_in_div id="queriesGrid" url=$queriesGridUrl}

{else}
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
		{url|assign:productionReadyFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.productionReady.ProductionReadyFilesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}
		{load_url_in_div id="productionReadyFilesGridDiv" url=$productionReadyFilesGridUrl}

		{url|assign:queriesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.queries.QueriesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}
		{load_url_in_div id="queriesGrid" url=$queriesGridUrl}

		{url|assign:representationsGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.articleGalleys.ArticleGalleyGridHandler" op="fetchGrid" submissionId=$submission->getId() escape=false}
		{load_url_in_div id="formatsGridContainer"|uniqid url=$representationsGridUrl}
	</div>
{/if}
</div>
