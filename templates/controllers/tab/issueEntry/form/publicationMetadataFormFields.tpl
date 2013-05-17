{**
 * catalog/form/catalogMetadataFormFields.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
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
			{rdelim}
		);
	{rdelim});
</script>

<form class="pkp_form" id="publicationMetadataEntryForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="saveForm"}">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="publicationMetadataFormFieldsNotification"}

	<input type="hidden" name="submissionId" value="{$submissionId|escape}" />
	<input type="hidden" name="stageId" value="{$stageId|escape}" />
	<input type="hidden" name="tabPos" value="1" />
	<input type="hidden" name="displayedInContainer" value="{$formParams.displayedInContainer|escape}" />
	<input type="hidden" name="tab" value="publication" />
	<input type="hidden" name="waivePublicationFee" value="0" />
	<input type="hidden" name="markAsPaid" value="0" />

	{if !$publicationFeeEnabled || $publicationPayment}
		{fbvFormArea id="schedulingInformation" title="editor.article.scheduleForPublication" class="border"}
			{fbvFormSection for="schedule"}
				{if $publishedArticle}
					{assign var=issueId value=$publishedArticle->getIssueId()}
				{else}
					{assign var=issueId value=0}
				{/if}
				{fbvElement type="select" id="issueId" required=true from=$issueOptions selected=$issueId translate=false label="editor.article.scheduleForPublication.toBeAssigned"}
			{/fbvFormSection}
		{/fbvFormArea}

		{if $publishedArticle}
			{fbvFormArea id="schedulingInformation" title="editor.issues.published" class="border"}
				{fbvFormSection for="publishedDate"}
					{fbvElement type="text" required=true id="datePublished" value=$publishedArticle->getDatePublished()|date_format:$dateFormatShort translate=false label="editor.issues.published" inline=true size=$fbvStyles.size.MEDIUM}
				{if $issueAccess && $issueAccess == $smarty.const.ISSUE_ACCESS_SUBSCRIPTION && $context->getSetting('publishingMode') == $smarty.const.PUBLISHING_MODE_SUBSCRIPTION}
					{fbvElement type="select" id="accessStatus" required=true from=$accessOptions selected=$publishedArticle->getAccessStatus() translate=false label="editor.issues.access" inline=true size=$fbvStyles.size.MEDIUM}
				{/if}
				{/fbvFormSection}
			{/fbvFormArea}
		{/if}
	{else}
		{fbvFormArea id="waivePayment" title="editor.article.payment.publicationFeeNotPaid" class="border"}
			{fbvFormSection for="waivePayment" size=$fbvStyles.size.MEDIUM}
				{fbvElement type="button" label="payment.paymentReceived" id="paymentReceivedButton" inline=true}
				{fbvElement type="button" label="payment.waive" id="waivePaymentButton" inline=true}
			{/fbvFormSection}
		{/fbvFormArea}
	{/if}

	{fbvFormButtons id="publicationMetadataFormSubmit" submitText="common.save"}
</form>
