{**
 * templates/subscriptions/institutionalSubscriptionForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
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
<form class="pkp_form" method="post" id="institutionalSubscriptionForm" action="{url op="updateSubscription" path="institutional"}">
	{if $subscriptionId}
	<input type="hidden" name="subscriptionId" value="{$subscriptionId|escape}" />
	{/if}
	{csrf}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="institutionalSubscriptionNotification"}

	{url|assign:subscriberSelectGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.users.subscriberSelect.SubscriberSelectGridHandler" op="fetchGrid" escape=false userId=$userId}
	{load_url_in_div id='subscriberSelectGridContainer' url=$subscriberSelectGridUrl}

	{fbvFormArea id="institutionalSubscriptionInfo"}
		{fbvElement type="select" required=true name="status" id="status" value=$status from=$validStatus label="manager.subscriptions.form.status" size=$fbvStyles.size.MEDIUM inline=true}
		{fbvElement type="select" required=true name="typeId" id="typeId" value=$typeId from=$subscriptionTypes label="manager.subscriptions.form.typeId" size=$fbvStyles.size.MEDIUM inline=true translate=false}
		{fbvElement type="text" required=true name="dateStart" id="dateStart" value=$dateStart label="manager.subscriptions.form.dateStart" size=$fbvStyles.size.MEDIUM inline=true class="datepicker"}
		{fbvElement type="text" required=true name="dateEnd" id="dateEnd" value=$dateEnd label="manager.subscriptions.form.dateEnd" size=$fbvStyles.size.MEDIUM inline=true class="datepicker"}
		{fbvElement type="text" required=true name="institutionName" id="institutionName" value=$institutionName label="manager.subscriptions.form.institutionName" size=$fbvStyles.size.LARGE}
		{fbvElement type="textarea" name="institutionMailingAddress" id="institutionMailingAddress" value=$institutionMailingAddress label="manager.subscriptions.form.institutionMailingAddress" size=$fbvStyles.size.LARGE}
		{fbvElement type="text" name="domain" id="domain" value=$domain label="manager.subscriptions.form.domain" size=$fbvStyles.size.LARGE}
		<span>{translate key="manager.subscriptions.form.domainInstructions"}</span>
		{fbvElement type="textarea" name="ipRanges" id="ipRanges" value=$ipRanges label="manager.subscriptions.form.ipRange" size=$fbvStyles.size.LARGE}
		<span>{translate key="manager.subscriptions.form.ipRangeInstructions"}</span>
		{fbvElement type="textarea" name="notes" id="notes" value=$notes label="manager.subscriptions.form.notes" size=$fbvStyles.size.LARGE}
	{/fbvFormArea}

	<span class="formRequired">{translate key="common.requiredField"}</span>

	{fbvFormButtons id="institutionalSubscriptionFormSubmit" submitText="common.save" hideCancel=true}
</form>
