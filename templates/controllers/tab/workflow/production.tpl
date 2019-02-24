{**
 * templates/controllers/tab/workflow/production.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Production workflow stage
 *}
<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
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
{help file="editorial-workflow/production" class="pkp_help_tab"}

<div id="production">
{include file="controllers/notification/inPlaceNotification.tpl" notificationId="productionNotification" requestOptions=$productionNotificationRequestOptions refreshOn="stageStatusUpdated"}

	<div id="submissionVersions" class="pkp_controllers_tab">
	  <ul>
		{foreach from=$submissionVersions item=submissionVersion}
		  <li>
			<a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.workflow.VersioningTabHandler" op="versioning" submissionId=$submission->getId() stageId=$stageId submissionVersion=$submissionVersion}">{translate key="submission.production.version" submissionVersion=$submissionVersion}</a>
		  </li>
		{/foreach}
		{if $newVersionAction}
		  <li>
			{include file="linkAction/linkAction.tpl" image="add_item" action=$newVersionAction contextId="newVersionTabContainer"}
		  </li>
		{/if}
	  </ul>
	</div>

	{capture assign=queriesGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.queries.QueriesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}{/capture}
	{load_url_in_div id="queriesGrid" url=$queriesGridUrl}
</div>
