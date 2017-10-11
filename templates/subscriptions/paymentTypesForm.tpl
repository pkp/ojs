{**
 * templates/subscriptions/paymentTypes.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Payment type form.
 *
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#paymentTypesForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="paymentTypesForm" method="post" action="{url op="savePaymentTypes"}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="paymentTypesFormNotification"}

	{fbvFormSection title="manager.payment.generalOptions" list=true}
		{fbvElement type="checkbox" name="journalPaymentsEnabled" id="journalPaymentsEnabled" checked=$journalPaymentsEnabled label="manager.payment.options.enablePayments" value="1"}
	{/fbvFormSection}

	{fbvFormSection title="manager.payment.authorFees"}
		<p>{translate key="manager.payment.authorFeesDescription"}
		{fbvElement type="text" name="publicationFee" id="publicationFee" label="manager.payment.options.publicationFee" value=$publicationFee size=$fbvStyles.size.SMALL}
	{/fbvFormSection}

	{fbvFormSection title="manager.payment.readerFees"}
		<p>{translate key="manager.payment.readerFeesDescription"}
		{fbvElement type="text" name="purchaseIssueFee" id="purchaseIssueFee" label="manager.payment.options.purchaseIssueFee" value=$purchaseIssueFee size=$fbvStyles.size.SMALL}
		{fbvElement type="text" name="purchaseArticleFee" id="purchaseArticleFee" label="manager.payment.options.purchaseArticleFee" value=$purchaseArticleFee size=$fbvStyles.size.SMALL}
		{fbvElement type="checkbox" name="restrictOnlyPdf" id="restrictOnlyPdf" checked=$restrictOnlyPdf label="manager.payment.options.onlypdf" value="1"}
	{/fbvFormSection}

	{fbvFormSection title="manager.payment.generalFees"}
		<p>{translate key="manager.payment.generalFeesDescription"}
		{fbvElement type="text" name="membershipFee" id="membershipFee" label="manager.payment.options.membershipFee" value=$membershipFee size=$fbvStyles.size.SMALL}
	{/fbvFormSection}

	{fbvFormButtons hideCancel=true submitText="common.save"}
	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>
