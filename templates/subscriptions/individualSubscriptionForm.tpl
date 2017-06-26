{**
 * templates/subscriptions/individualSubscriptionForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Individual subscription form under journal management.
 *
 *}
<br/>
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#individualSubscriptionForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<form class="pkp_form" method="post" id="individualSubscriptionForm" action="{url op="updateSubscription"}">
	{if $subscriptionId}
		<input type="hidden" name="subscriptionId" value="{$subscriptionId|escape}" />
	{/if}
	{csrf}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="individualSubscriptionNotification"}

	{url|assign:subscriberSelectGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.users.subscriberSelect.SubscriberSelectGridHandler" op="fetchGrid" escape=false userId=$userId}
	{load_url_in_div id='subscriberSelectGridContainer' url=$subscriberSelectGridUrl}

	{fbvFormArea id="subscriptionFormArea"}
		{fbvFormSection}
			{fbvElement type="select" required=true name="status" id="status" value=$status from=$validStatus label="manager.subscriptions.form.status" size=$fbvStyles.size.MEDIUM inline=true}
			{fbvElement type="select" required=true name="typeId" id="typeId" value=$typeId from=$subscriptionTypes label="manager.subscriptions.form.typeId" size=$fbvStyles.size.MEDIUM inline=true translate=false}
			{fbvElement type="text" required=true name="dateStart" id="dateStart" value=$dateStart label="manager.subscriptions.form.dateStart" size=$fbvStyles.size.MEDIUM inline=true class="datepicker"}
			{fbvElement type="text" required=true name="dateEnd" id="dateEnd" value=$dateEnd label="manager.subscriptions.form.dateEnd" size=$fbvStyles.size.MEDIUM inline=true class="datepicker"}
			{fbvElement type="text" name="membership" id="membership" value=$membership label="manager.subscriptions.form.membership" size=$fbvStyles.size.MEDIUM inline=true}
			{fbvElement type="text" name="referenceNumber" id="referenceNumber" value=$referenceNumber label="manager.subscriptions.form.referenceNumber" size=$fbvStyles.size.MEDIUM inline=true}
		{/fbvFormSection}
		{fbvFormSection}
			{fbvElement type="textarea" name="notes" id="notes" value=$notes label="manager.subscriptions.form.notes" size=$fbvStyles.size.LARGE rich=true}
		{/fbvFormSection}
	{/fbvFormArea}

	<span class="formRequired">{translate key="common.requiredField"}</span>

	{fbvFormButtons id="mastheadFormSubmit" submitText="common.save" hideCancel=true}
</form>
