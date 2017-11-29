{**
 * templates/user/userIndividualSubscriptionForm.tpl
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User purchase individual subscription form
 *
 *}
{include file="common/header.tpl" pageTitle="user.subscriptions.purchaseIndividualSubscription" pageId="user.subscriptions.userIndividualSubscriptionForm"}

<div class="pkp_page_content pkp_page_purchaseIndividualSubscription">

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#subscriptionForm').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" method="post" id="subscriptionForm" action="{url op="payPurchaseSubscription" path="individual"|to_array:$subscriptionId}">
	{csrf}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="subscriptionFormNotification"}

	{fbvFormSection}
		{fbvElement type="select" label="user.subscriptions.form.typeId" name="typeId" id="typeId" from=$subscriptionTypes translate=false selected=$typeId size=$fbvStyles.size.MEDIUM}
	{/fbvFormSection}
	{fbvFormSection}
		{fbvElement type="text" label="user.subscriptions.form.membership" name="membership" id="membership" value=$membership size=$fbvStyles.size.MEDIUM}
	{/fbvFormSection}

	{fbvFormButtons hideCancel=true submitText="common.save"}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>

</div>

{include file="common/footer.tpl"}
