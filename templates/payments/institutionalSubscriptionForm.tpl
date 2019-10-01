{**
 * templates/payments/institutionalSubscriptionForm.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Individual subscription form under journal management.
 *
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#institutionalSubscriptionForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<form class="pkp_form" method="post" id="institutionalSubscriptionForm" action="{url op="updateSubscription"}">
	{if $subscriptionId}
	<input type="hidden" name="subscriptionId" value="{$subscriptionId|escape}" />
	{/if}
	{csrf}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="institutionalSubscriptionNotification"}

	{capture assign=subscriberSelectGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.users.subscriberSelect.SubscriberSelectGridHandler" op="fetchGrid" escape=false userId=$userId}{/capture}
	{load_url_in_div id='subscriberSelectGridContainer' url=$subscriberSelectGridUrl}

	{fbvFormArea id="institutionalSubscriptionFormArea"}
		{fbvFormSection title="manager.subscriptions.form.typeId"}
			{fbvElement type="select" required=true name="typeId" id="typeId" selected=$typeId from=$subscriptionTypes label="manager.subscriptions.form.typeId" size=$fbvStyles.size.MEDIUM inline=true translate=false}
			{fbvElement type="select" required=true name="status" id="status" selected=$status from=$validStatus label="manager.subscriptions.form.status" size=$fbvStyles.size.SMALL inline=true}
		{/fbvFormSection}
		{fbvFormSection title="common.date"}
			{fbvElement type="text" required=true name="dateStart" id="dateStart" value=$dateStart label="manager.subscriptions.form.dateStart" size=$fbvStyles.size.SMALL inline=true class="datepicker"}
			{fbvElement type="text" required=true name="dateEnd" id="dateEnd" value=$dateEnd label="manager.subscriptions.form.dateEnd" size=$fbvStyles.size.SMALL inline=true class="datepicker"}
		{/fbvFormSection}
		{fbvFormSection label="manager.subscriptions.form.institutionName"}
			{fbvElement type="text" required=true name="institutionName" id="institutionName" value=$institutionName size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection label="manager.subscriptions.form.institutionMailingAddress"}
			{fbvElement type="textarea" name="institutionMailingAddress" id="institutionMailingAddress" value=$institutionMailingAddress}
		{/fbvFormSection}
		{fbvFormSection label="manager.subscriptions.form.domain"}
			{fbvElement type="text" name="domain" id="domain" value=$domain label="manager.subscriptions.form.domainInstructions" size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection label="manager.subscriptions.form.ipRange"}
			{fbvElement type="textarea" name="ipRanges" id="ipRanges" value=$ipRanges label="manager.subscriptions.form.ipRangeInstructions" size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection label="manager.subscriptions.form.referenceNumber"}
			{fbvElement type="text" name="referenceNumber" id="referenceNumber" value=$referenceNumber size=$fbvStyles.size.MEDIUM inline=true}
		{/fbvFormSection}
		{fbvFormSection label="manager.subscriptions.form.notes"}
			{fbvElement type="textarea" name="notes" id="notes" value=$notes rich=true}
		{/fbvFormSection}
	{/fbvFormArea}

	<span class="formRequired">{translate key="common.requiredField"}</span>

	{fbvFormButtons id="institutionalSubscriptionFormSubmit" submitText="common.save" hideCancel=true}
</form>
