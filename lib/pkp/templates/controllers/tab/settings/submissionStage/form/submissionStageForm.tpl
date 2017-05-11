{**
 * controllers/tab/settings/submissionStage/form/submissionStageForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission stage management form.
 *
 *}

{* Help Link *}
{help file="settings.md" section="workflow-submission" class="pkp_help_tab"}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#submissionStageForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="submissionStageForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.PublicationSettingsTabHandler" op="saveFormData" tab="submissionStage"}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="submissionStageFormNotification"}

	{fbvFormArea id="authorGuidelinesArea"}
		{fbvFormSection label="manager.setup.authorGuidelines" description="manager.setup.authorGuidelines.description"}
			{fbvElement type="textarea" multilingual=true name="authorGuidelines" id="authorGuidelines" value=$authorGuidelines rich=true}
		{/fbvFormSection}
	{/fbvFormArea}

	{url|assign:submissionChecklistGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.settings.submissionChecklist.SubmissionChecklistGridHandler" op="fetchGrid" escape=false}
	{load_url_in_div id="submissionChecklistGridDiv" url=$submissionChecklistGridUrl}

	{fbvFormArea id="notificationSettings"}
		{fbvFormSection label="manager.setup.notifications" for="copySubmissionAckPrimaryContact" description="manager.setup.notifications.description" list=true}
			{fbvElement type="checkbox" id="copySubmissionAckPrimaryContact" disabled=$submissionAckDisabled checked=$copySubmissionAckPrimaryContact label="manager.setup.notifications.copyPrimaryContact"}
		{/fbvFormSection}
		{fbvFormSection}
			{fbvElement type="text" disabled=$submissionAckDisabled id="copySubmissionAckAddress" value=$copySubmissionAckAddress size=$fbvStyles.size.MEDIUM label="manager.setup.notifications.copySpecifiedAddress"}
		{/fbvFormSection}
		{if $submissionAckDisabled}
			{translate key="manager.setup.notifications.submissionAckDisabled"}
		{/if}
	{/fbvFormArea}

	{url|assign:metadataGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.settings.metadata.MetadataGridHandler" op="fetchGrid" escape=false}
	{load_url_in_div id="metadataGridContainer" url=$metadataGridUrl}

	{if !$wizardMode}
		{fbvFormButtons id="submissionStageFormSubmit" submitText="common.save" hideCancel=true}
	{/if}
</form>
