{**
 * templates/workflow/workflow.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the workflow tab structure.
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

<script type="text/javascript">
	// Initialize JS handler.
	$(function() {ldelim}
		$('#submissionWorkflow').pkpHandler(
			'$.pkp.pages.workflow.WorkflowHandler'
		);
	{rdelim});
</script>

<div id="submissionWorkflow" class="pkp_submission_workflow">

	{url|assign:submissionHeaderUrl op="submissionHeader" submissionId=$submission->getId() stageId=$stageId contextId="submission" escape=false}
	{load_url_in_div id="submissionHeaderDiv" url=$submissionHeaderUrl class="pkp_page_header"}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="workflowNotification" requestOptions=$workflowNotificationRequestOptions}

</div>

{include file="common/footer.tpl"}
