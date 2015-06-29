{**
 * templates/authorDashboard/authorDashboard.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the author dashboard.
 *}
{strip}
	{assign var=primaryAuthor value=$submission->getPrimaryAuthor()}
	{if !$primaryAuthor}
		{assign var=authors value=$submission->getAuthors()}
		{assign var=primaryAuthor value=$authors[0]}
	{/if}
	{assign var="pageTitleTranslated" value=$primaryAuthor->getLastName()|concat:", <em>":$submission->getLocalizedTitle():"</em>"|truncate:50}
	{include file="common/header.tpl" suppressPageTitle=true}
{/strip}

{include file="authorDashboard/top.tpl"}

{assign var=selectedTabIndex value=0}
{foreach from=$workflowStages item=stage}
	{if $stage.id < $submission->getStageId()}
		{assign var=selectedTabIndex value=$selectedTabIndex+1}
	{/if}
{/foreach}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#stageTabs').pkpHandler(
			'$.pkp.controllers.tab.workflow.WorkflowTabHandler',
			{ldelim}
				selected: {$selectedTabIndex},
				emptyLastTab: true
			{rdelim}
		);
	{rdelim});
</script>
<div style="clear:both">
	<div id="stageTabs" class="pkp_controllers_tab">
		<ul>
			{foreach from=$workflowStages item=stage}
				<li class="workflowStage">
					<a name="stage-{$stage.path}" class="{$stage.path} stageId{$stage.id}" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.authorDashboard.AuthorDashboardTabHandler" op="fetchTab" submissionId=$submission->getId() stageId=$stage.id escape=false}">
					{translate key=$stage.translationKey}
					<div class="stageState">
							{translate key=$stage.statusKey}
						</div>
					</a>
				</li>
			{/foreach}
		</ul>
	</div>
</div>

{include file="common/footer.tpl"}
