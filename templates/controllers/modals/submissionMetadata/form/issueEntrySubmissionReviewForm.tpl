{**
 * controllers/modals/submissionMetadata/form/issueEntrySubmissionReviewForm.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display a submission's issue entry form.
 *
 *}
{* generate a unique ID for the form *}
{assign var="submissionMetadataViewFormId" value="submissionMetadataViewForm-"|uniqid|escape}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#{$submissionMetadataViewFormId}').pkpHandler(
			'$.pkp.controllers.form.AjaxFormHandler',
			{ldelim}
				trackFormChanges: true
			{rdelim}
		);
	{rdelim});
</script>

<form class="pkp_form" id="{$submissionMetadataViewFormId}" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="saveForm"}">
	{csrf}
	{assign var="notificationId" value="submissionMetadataViewFormNotification-"|uniqid|escape}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId=$notificationId}

	<input type="hidden" name="submissionId" value="{$submissionId|escape}" />
	<input type="hidden" name="stageId" value="{$stageId|escape}" />
	<input type="hidden" name="displayedInContainer" value="{$formParams.displayedInContainer|escape}" />
	<input type="hidden" name="tab" value="submission" />

	{include file="submission/form/section.tpl" readOnly=$formParams.readOnly}

	{include file="core:submission/submissionMetadataFormTitleFields.tpl" readOnly=$formParams.readOnly}

	<!--  Contributors -->

	{if !$formParams.hideSubmit || !$formParams.anonymous}
		{* generate a unique ID for the form *}
		{assign var="authorsGridContainer" value="authorsGridContainer-"|uniqid|escape}
		{url|assign:authorGridUrl router=$smarty.const.ROUTE_COMPONENT  component="grid.users.author.AuthorGridHandler" op="fetchGrid" submissionId=$submissionId stageId=$stageId escape=false}
		{load_url_in_div id=$authorsGridContainer url="$authorGridUrl"}
	{/if}

	{include file="submission/submissionMetadataFormFields.tpl" readOnly=$formParams.readOnly}

	{if !$formParams.hideSubmit}
		{fbvFormButtons id="submissionMetadataFormSubmit" submitText="common.save"}
	{/if}
</form>
