{**
 * templates/controllers/tab/issueEntry/form/publicationMetadataFormFields.tpl
 *
 * Copyright (c) 2016-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#publicationMetadataEntryForm').pkpHandler(
			'$.pkp.controllers.tab.issueEntry.form.IssueEntryPublicationMetadataFormHandler',
			{ldelim}
				trackFormChanges: true,
				arePermissionsAttached: {if $arePermissionsAttached}true{else}false{/if}
			{rdelim}
		);
	{rdelim});
</script>

<form class="pkp_form" id="publicationMetadataEntryForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="savePublicationMetadataForm"}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="publicationMetadataFormFieldsNotification"}

	<input type="hidden" name="submissionId" value="{$submissionId|escape}" />
	<input type="hidden" name="stageId" value="{$stageId|escape}" />

	{fbvFormArea id="pagesInformation" title="editor.issues.pages"}
		{fbvFormSection for="customExtras"}
			{fbvElement type="text" readOnly=$formParams.readOnly id="pages" label="editor.issues.pages" value=$submission->getPages() inline=true size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="permissions" title="submission.permissions"}
		{fbvFormSection list=true}
			{fbvElement type="checkbox" id="attachPermissions" label="submission.attachPermissions" disabled=$formParams.readOnly}
		{/fbvFormSection}
		{fbvFormSection}
			{fbvElement type="text" id="licenseURL" label="submission.licenseURL" readOnly=$formParams.readOnly value=$licenseURL}
			{fbvElement type="text" id="copyrightHolder" label="submission.copyrightHolder" readOnly=$formParams.readOnly value=$copyrightHolder multilingual=true size=$fbvStyles.size.MEDIUM inline=true}
			{fbvElement type="text" id="copyrightYear" label="submission.copyrightYear" readOnly=$formParams.readOnly value=$copyrightYear size=$fbvStyles.size.SMALL inline=true}
		{/fbvFormSection}
	{/fbvFormArea}
	{if !$formParams.hideSubmit}
		{fbvFormButtons id="publicationMetadataFormSubmit" submitText="common.save"}
	{/if}
</form>
