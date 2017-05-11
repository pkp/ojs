{**
 * templates/authorDashboard/authorDashboard.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
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
	{assign var=submissionTitleSafe value=$submission->getLocalizedTitle()|strip_unsafe_html}
	{if $primaryAuthor}
		{assign var="pageTitleTranslated" value=$primaryAuthor->getLastName()|concat:", ":$submissionTitleSafe}
	{else}
		{assign var="pageTitleTranslated" value=$submissionTitleSafe}
	{/if}
	{include file="common/header.tpl" suppressPageTitle=true}
{/strip}

<div id="submissionWorkflow" class="pkp_submission_workflow">

	<div id="submissionHeader" class="pkp_page_header">
		<div class="pkp_page_title">
			<h1 class="pkp_submission_title">
				<span class="pkp_screen_reader">{translate key="submission.submissionTitle"}</span>
				{$submission->getLocalizedTitle()}
			</h1>
			<div class="pkp_submission_author">
				<span class="pkp_screen_reader">{translate key="user.role.author_s"}</span>
				{$submission->getAuthorString()}
			</div>
			<ul class="pkp_submission_actions">
				{if $uploadFileAction}
					<li>
						{include file="linkAction/linkAction.tpl" action=$uploadFileAction contextId="authorDashboard"}
					</li>
				{/if}
				<li>
					{include file="linkAction/linkAction.tpl" action=$submissionLibraryAction contextId="authorDashboard"}
				</li>
				<li>
					{include file="linkAction/linkAction.tpl" action=$viewMetadataAction contextId="authorDashboard"}
				</li>
			</ul>
		</div>
	</div>

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="authorDashboardNotification" requestOptions=$authorDashboardNotificationRequestOptions}

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
	<div id="stageTabs" class="pkp_controllers_tab">
		<ul>
			{foreach from=$workflowStages item=stage}
				<li class="pkp_workflow_{$stage.path} stageId{$stage.id}{if $stage.statusKey} initiated{/if}">
					<a name="stage-{$stage.path}" class="{$stage.path} stageId{$stage.id}" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.authorDashboard.AuthorDashboardTabHandler" op="fetchTab" submissionId=$submission->getId() stageId=$stage.id escape=false}">
						{translate key=$stage.translationKey}
						{if $stage.statusKey}
							<span class="pkp_screen_reader">
								{translate key=$stage.statusKey}
							</span>
						{/if}
					</a>
				</li>
			{/foreach}
		</ul>
	</div>
</div>

{include file="common/footer.tpl"}
