{**
 * templates/payments/subscriptionTypeForm.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subscription type form under journal management.
 *
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#subscriptionTypeForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
		$('#individual, #institutional').on('change', function() {ldelim}
			if ($('#institutional').prop('checked')) {ldelim}
				$('#membership').prop('checked', false).prop('disabled', true);
			{rdelim} else {ldelim}
				$('#membership').prop('disabled', false);
			{rdelim}
		{rdelim});
	{rdelim});
</script>

<form class="pkp_form" id="subscriptionTypeForm" method="post" action="{url op="updateSubscriptionType"}">
	{csrf}
	{if $typeId}
		<input type="hidden" name="typeId" value="{$typeId|escape}" />
	{/if}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="subscriptionTypeNotification"}

	{fbvFormSection label="manager.subscriptionTypes.form.typeName"}
		{fbvElement type="text" required=true name="name" id="typeName" value=$name size=$fbvStyles.size.MEDIUM multilingual=true}
	{/fbvFormSection}

	{fbvFormSection label="manager.subscriptionTypes.form.description"}
		{fbvElement type="textarea" name="description" id="description" value=$description rich=true multilingual=true}
	{/fbvFormSection}

	{fbvFormSection title="manager.subscriptionTypes.form.cost"}
		{fbvElement type="select" required=true name="currency" id="currency" selected=$currency from=$validCurrencies label="manager.subscriptionTypes.form.currency" size=$fbvStyles.size.SMALL inline=true translate=false}
		{fbvElement type="text" required=true name="cost" id="cost" value=$cost label="manager.subscriptionTypes.form.cost" description="manager.subscriptionTypes.form.costInstructions" size=$fbvStyles.size.SMALL inline=true}
	{/fbvFormSection}

	{fbvFormSection label="manager.subscriptionTypes.form.format"}
		{fbvElement type="select" required=true name="format" id="format" selected=$format from=$validFormats size=$fbvStyles.size.SMALL translate=false inline=true}
	{/fbvFormSection}

	{fbvFormSection label="manager.subscriptionTypes.form.duration"}
		{fbvElement type="text" name="duration" id="duration" value=$duration size=$fbvStyles.size.SMALL label="manager.subscriptionTypes.form.durationInstructions" inline=true}
	{/fbvFormSection}

	{fbvFormSection title="manager.subscriptionTypes.form.subscriptions" list=true}
		{fbvElement type="radio" name="institutional" id="individual" checked=$institutional|compare:"0" label="manager.subscriptionTypes.form.individual" value="0" disabled=$typeId}
		{fbvElement type="radio" name="institutional" id="institutional" checked=$institutional|compare:"1" label="manager.subscriptionTypes.form.institutional" value="1" disabled=$typeId}
	{/fbvFormSection}

	{fbvFormSection title="manager.subscriptionTypes.form.options" list=true}
		{fbvElement type="checkbox" name="membership" id="membership" checked=$membership value="1" label="manager.subscriptionTypes.form.membership"}
		{fbvElement type="checkbox" name="disable_public_display" id="disable_public_display" checked=$disable_public_display value="1" label="manager.subscriptionTypes.form.public"}
	{/fbvFormSection}

	<span class="formRequired">{translate key="common.requiredField"}</span>

	{fbvFormButtons id="mastheadFormSubmit" submitText="common.save" hideCancel=true}
</form>
