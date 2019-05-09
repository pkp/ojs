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
	<input type="hidden" name="waivePublicationFee" value="0" />
	<input type="hidden" name="markAsPaid" value="0" />

	{if !$publicationFeeEnabled || $publicationPayment}
		{fbvFormArea id="schedulingInformation" title="editor.article.scheduleForPublication"}
			{fbvFormSection for="schedule"}
				{if $publishedArticle}
					{assign var=issueId value=$publishedArticle->getIssueId()}
				{else}
					{assign var=issueId value=0}
				{/if}
				{fbvElement type="select" disabled=$formParams.readOnly id="issueId" required=true from=$issueOptions selected=$issueId translate=false label="editor.article.scheduleForPublication.toBeAssigned"}
			{/fbvFormSection}
		{/fbvFormArea}

		{fbvFormArea id="pagesInformation" title="editor.issues.pages"}
			{fbvFormSection for="customExtras"}
				{fbvElement type="text" readOnly=$formParams.readOnly id="pages" label="editor.issues.pages" value=$submission->getPages() inline=true size=$fbvStyles.size.MEDIUM}
			{/fbvFormSection}
		{/fbvFormArea}

		{if $publishedArticle}
			{fbvFormArea id="schedulingInformation" title="editor.issues.published"}
				{fbvFormSection for="publishedDate"}
					{fbvElement type="text" required=true id="datePublished" disabled=$formParams.readOnly value=$publishedArticle->getDatePublished() translate=false label="editor.issues.published" inline=true size=$fbvStyles.size.MEDIUM class="datepicker"}
				{if $issueAccess && $issueAccess == $smarty.const.ISSUE_ACCESS_SUBSCRIPTION && $context->getData('publishingMode') == $smarty.const.PUBLISHING_MODE_SUBSCRIPTION}
					{fbvElement type="select" id="accessStatus" disabled=$formParams.readOnly required=true from=$accessOptions selected=$publishedArticle->getAccessStatus() translate=false label="editor.issues.access" inline=true size=$fbvStyles.size.MEDIUM}
				{/if}
				{/fbvFormSection}
			{/fbvFormArea}
		{/if}
	{else}
		{fbvFormArea id="waivePayment" title="editor.article.payment.publicationFeeNotPaid"}
			{fbvFormSection for="waivePayment" size=$fbvStyles.size.MEDIUM}
				{fbvElement type="button" disabled=$formParams.readOnly label="payment.paymentReceived" id="paymentReceivedButton" inline=true}
				{fbvElement type="button" disabled=$formParams.readOnly label="payment.waive" id="waivePaymentButton" inline=true}
			{/fbvFormSection}
		{/fbvFormArea}
	{/if}

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
