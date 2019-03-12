{**
 * templates/payments/subscriptionPolicyForm.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Setup subscription policies.
 *
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#subscriptionPolicies').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="subscriptionPolicies" method="post" action="{url op="saveSubscriptionPolicies"}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="subscriptionPolicyFormNotification"}

	{fbvFormSection label="manager.subscriptionPolicies.subscriptionContact"}
		<p>{translate key="manager.subscriptionPolicies.subscriptionContactDescription"}</p>
		{fbvElement type="text" label="user.name" required=true id="subscriptionName" value=$subscriptionName maxlength="60" inline=true size=$fbvStyles.size.MEDIUM}
		{fbvElement type="text" label="user.email" id="subscriptionEmail" value=$subscriptionEmail size=$fbvStyles.size.MEDIUM required=true}
		{fbvElement type="text" label="user.phone" name="subscriptionPhone" id="subscriptionPhone" value=$subscriptionPhone maxlength="24" size=$fbvStyles.size.SMALL}
		{fbvElement type="textarea" id="subscriptionMailingAddress" value=$subscriptionMailingAddress height=$fbvStyles.height.SHORT required=true label="common.mailingAddress"}
	{/fbvFormSection}

	{fbvFormSection label="manager.subscriptionPolicies.subscriptionAdditionalInformation"}
		<p>{translate key="manager.subscriptionPolicies.subscriptionAdditionalInformationDescription"}</p>
		{fbvElement type="textarea" id="subscriptionAdditionalInformation" value=$subscriptionAdditionalInformation rich=true multilingual=true}
	{/fbvFormSection}

	{fbvFormSection label="manager.subscriptionPolicies.expiry" list=1}
		<p>{translate key="manager.subscriptionPolicies.expiryDescription"}</p>
		{fbvElement type="radio" id="subscriptionExpiryPartial-0" name="subscriptionExpiryPartial" value=0 checked=$subscriptionExpiryPartial|compare:0 label="manager.subscriptionPolicies.expiryFull"}
		<span>{translate key="manager.subscriptionPolicies.expiryFullDescription"}</span>
		{fbvElement type="radio" id="subscriptionExpiryPartial-1" name="subscriptionExpiryPartial" value=1 checked=$subscriptionExpiryPartial|compare:1 label="manager.subscriptionPolicies.expiryPartial"}
		<span>{translate key="manager.subscriptionPolicies.expiryPartialDescription"}</span>
	{/fbvFormSection}

	{fbvFormSection label="manager.subscriptionPolicies.expiryReminders"}
		<p>{translate key="manager.subscriptionPolicies.expiryRemindersDescription"}</p>
		{fbvElement type="select" id="numMonthsBeforeSubscriptionExpiryReminder" name="numMonthsBeforeSubscriptionExpiryReminder" selected=$numMonthsBeforeSubscriptionExpiryReminder from=$validNumMonthsBeforeExpiry label="manager.subscriptionPolicies.expiryReminderBeforeMonths" disabled=$scheduledTasksEnabled|compare:0 size=$fbvStyles.size.MEDIUM translate=false inline=true}
		{fbvElement type="select" id="numWeeksBeforeSubscriptionExpiryReminder" name="numWeeksBeforeSubscriptionExpiryReminder" selected=$numWeeksBeforeSubscriptionExpiryReminder from=$validNumWeeksBeforeExpiry label="manager.subscriptionPolicies.expiryReminderBeforeWeeks" disabled=$scheduledTasksEnabled|compare:0 size=$fbvStyles.size.MEDIUM translate=false inline=true}
		{fbvElement type="select" id="numWeeksAfterSubscriptionExpiryReminder" name="numWeeksAfterSubscriptionExpiryReminder" selected=$numWeeksAfterSubscriptionExpiryReminder from=$validNumWeeksAfterExpiry label="manager.subscriptionPolicies.expiryReminderAfterWeeks" disabled=$scheduledTasksEnabled|compare:0 size=$fbvStyles.size.MEDIUM translate=false inline=true}
		{fbvElement type="select" id="numMonthsAfterSubscriptionExpiryReminder" name="numMonthsAfterSubscriptionExpiryReminder" selected=$numMonthsAfterSubscriptionExpiryReminder from=$validNumMonthsAfterExpiry label="manager.subscriptionPolicies.expiryReminderAfterMonths" disabled=$scheduledTasksEnabled|compare:0 size=$fbvStyles.size.MEDIUM translate=false inline=true}

		{if !$scheduledTasksEnabled}
			<span>{translate key="manager.subscriptionPolicies.expiryRemindersDisabled"}</span>
		{/if}
	{/fbvFormSection}

	{fbvFormSection label="manager.subscriptionPolicies.onlinePaymentNotifications" list=true}
		{if $paymentsEnabled}
			{assign var=paymentsEnabled value=true}
		{/if}
		<p>{translate key="manager.subscriptionPolicies.onlinePaymentNotificationsDescription"}</p>

		{fbvElement type="checkbox" id="enableSubscriptionOnlinePaymentNotificationPurchaseIndividual" name="enableSubscriptionOnlinePaymentNotificationPurchaseIndividual" value=1 checked=$enableSubscriptionOnlinePaymentNotificationPurchaseIndividual label="manager.subscriptionPolicies.enableSubscriptionOnlinePaymentNotificationPurchaseIndividual" disabled=$paymentsEnabled|compare:0}
		{fbvElement type="checkbox" id="enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional" name="enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional" value=1 checked=$enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional label="manager.subscriptionPolicies.enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional" disabled=$paymentsEnabled|compare:0}
		{fbvElement type="checkbox" id="enableSubscriptionOnlinePaymentNotificationRenewIndividual" name="enableSubscriptionOnlinePaymentNotificationRenewIndividual" value=1 checked=$enableSubscriptionOnlinePaymentNotificationRenewIndividual label="manager.subscriptionPolicies.enableSubscriptionOnlinePaymentNotificationRenewIndividual" disabled=$paymentsEnabled|compare:0}
		{fbvElement type="checkbox" id="enableSubscriptionOnlinePaymentNotificationRenewInstitutional" name="enableSubscriptionOnlinePaymentNotificationRenewInstitutional" value=1 checked=$enableSubscriptionOnlinePaymentNotificationRenewInstitutional label="manager.subscriptionPolicies.enableSubscriptionOnlinePaymentNotificationRenewInstitutional" disabled=$paymentsEnabled|compare:0}

		{if !$paymentsEnabled}
			<span>{translate key="manager.subscriptionPolicies.onlinePaymentDisabled"}<span>
		{/if}
	{/fbvFormSection}
	{fbvFormSection label="manager.subscriptionPolicies.openAccessOptions" list=true}
		{fbvElement type="checkbox" id="enableOpenAccessNotification" name="enableOpenAccessNotification" value=1 checked=$enableOpenAccessNotification label="manager.subscriptionPolicies.openAccessNotificationDescription" disabled=$scheduledTasksEnabled|compare:0}
	{/fbvFormSection}

	{fbvFormButtons hideCancel=true submitText="common.save"}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>
