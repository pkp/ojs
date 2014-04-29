{**
 * templates/subscription/subscriptionPolicyForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Setup subscription policies.
 *
 *}
{strip}
{assign var="pageTitle" value="manager.subscriptionPolicies"}
{assign var="pageId" value="manager.subscriptionPolicies"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="subscriptionsSummary"}">{translate key="manager.subscriptions.summary"}</a></li>
	<li><a href="{url op="subscriptions" path="individual"}">{translate key="manager.individualSubscriptions"}</a></li>
	<li><a href="{url op="subscriptions" path="institutional"}">{translate key="manager.institutionalSubscriptions"}</a></li>
	<li><a href="{url op="subscriptionTypes"}">{translate key="manager.subscriptionTypes"}</a></li>
	<li class="current"><a href="{url op="subscriptionPolicies"}">{translate key="manager.subscriptionPolicies"}</a></li>
	<li><a href="{url op="payments"}">{translate key="manager.payments"}</a></li>
</ul>

{if $subscriptionPoliciesSaved}
<br/>
{translate key="manager.subscriptionPolicies.subscriptionPoliciesSaved"}<br />
{/if}

<form id="subscriptionPolicies" method="post" action="{url op="saveSubscriptionPolicies"}">
{include file="common/formErrors.tpl"}

	<script type="text/javascript">
		{literal}
		<!--
			function toggleAllowSetDelayedOpenAccessDuration(form) {
				form.delayedOpenAccessDuration.disabled = !form.delayedOpenAccessDuration.disabled;
			}
			function toggleAllowSetBeforeWeeksReminder(form) {
				form.numWeeksBeforeSubscriptionExpiryReminder.disabled = !form.numWeeksBeforeSubscriptionExpiryReminder.disabled;
			}
			function toggleAllowSetAfterWeeksReminder(form) {
				form.numWeeksAfterSubscriptionExpiryReminder.disabled = !form.numWeeksAfterSubscriptionExpiryReminder.disabled;
			}
		// -->
		{/literal}
	</script>

<div id="subscriptionContact">
<h3>{translate key="manager.subscriptionPolicies.subscriptionContact"}</h3>
<p>{translate key="manager.subscriptionPolicies.subscriptionContactDescription"}</p>
<table width="100%" class="data">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"subscriptionPoliciesUrl" op="subscriptionPolicies" escape=false}
			{form_language_chooser form="subscriptionPolicies" url=$subscriptionPoliciesUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="subscriptionName" key="user.name"}</td>
		<td width="80%" class="value"><input type="text" name="subscriptionName" id="subscriptionName" value="{$subscriptionName|escape}" size="30" maxlength="60" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="subscriptionEmail" key="user.email"}</td>
		<td width="80%" class="value"><input type="text" name="subscriptionEmail" id="subscriptionEmail" value="{$subscriptionEmail|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="subscriptionPhone" key="user.phone"}</td>
		<td width="80%" class="value"><input type="text" name="subscriptionPhone" id="subscriptionPhone" value="{$subscriptionPhone|escape}" size="15" maxlength="24" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="subscriptionFax" key="user.fax"}</td>
		<td width="80%" class="value"><input type="text" name="subscriptionFax" id="subscriptionFax" value="{$subscriptionFax|escape}" size="15" maxlength="24" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="subscriptionMailingAddress" key="common.mailingAddress"}</td>
		<td width="80%" class="value"><textarea name="subscriptionMailingAddress" id="subscriptionMailingAddress" rows="6" cols="40" class="textArea">{$subscriptionMailingAddress|escape}</textarea></td>
	</tr>
</table>
</div>

<div class="separator"></div>

<div id="additionalInformation">
<h3>{translate key="manager.subscriptionPolicies.subscriptionAdditionalInformation"}</h3>
<p>{translate key="manager.subscriptionPolicies.subscriptionAdditionalInformationDescription"}</p>
<p>
	<textarea name="subscriptionAdditionalInformation[{$formLocale|escape}]" id="subscriptionAdditionalInformation" rows="12" cols="60" class="textArea">{$subscriptionAdditionalInformation[$formLocale]|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.subscriptionPolicies.htmlInstructions"}</span>
</p>
</div>

<div class="separator"></div>

<div id="expiry">
<h3>{translate key="manager.subscriptionPolicies.expiry"}</h3>
<p>{translate key="manager.subscriptionPolicies.expiryDescription"}</p>

<p>{translate key="manager.subscriptionPolicies.expirySelectOne"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label" align="right">
			<input type="radio" name="subscriptionExpiryPartial" id="subscriptionExpiryPartial-0" value="0"{if not $subscriptionExpiryPartial} checked="checked"{/if} />
		</td>
		<td width="95%" class="value">
			<strong>{fieldLabel name="subscriptionExpiryPartial-0" key="manager.subscriptionPolicies.expiryFull"}</strong>
			<br />
			<span class="instruct">{translate key="manager.subscriptionPolicies.expiryFullDescription"}</span>
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label" align="right">
			<input type="radio" name="subscriptionExpiryPartial" id="subscriptionExpiryPartial-1" value="1"{if $subscriptionExpiryPartial} checked="checked"{/if} />
		</td>
		<td width="95%" class="value">
			<strong>{fieldLabel name="subscriptionExpiryPartial-1" key="manager.subscriptionPolicies.expiryPartial"}</strong>
			<br />
			<span class="instruct">{translate key="manager.subscriptionPolicies.expiryPartialDescription"}</span>
		</td>
	</tr>
</table>
</div>

<div class="separator"></div>

<div id="expiryReminders">
<h3>{translate key="manager.subscriptionPolicies.expiryReminders"}</h3>
<p>{translate key="manager.subscriptionPolicies.expiryRemindersDescription"}</p>
<p>
	<input type="checkbox" name="enableSubscriptionExpiryReminderBeforeWeeks" id="enableSubscriptionExpiryReminderBeforeWeeks" value="1" onclick="toggleAllowSetBeforeWeeksReminder(this.form)"{if !$scheduledTasksEnabled} disabled="disabled" {elseif $enableSubscriptionExpiryReminderBeforeWeeks} checked="checked"{/if} />&nbsp;
	{fieldLabel name="enableSubscriptionExpiryReminderBeforeWeeks" key="manager.subscriptionPolicies.expiryReminderBeforeWeeks1"}
	<select name="numWeeksBeforeSubscriptionExpiryReminder" id="numWeeksBeforeSubscriptionExpiryReminder" class="selectMenu"{if not $enableSubscriptionExpiryReminderBeforeWeeks || !$scheduledTasksEnabled} disabled="disabled"{/if}>{html_options options=$validWeeks selected=$numWeeksBeforeSubscriptionExpiryReminder}</select>
	{fieldLabel name="numWeeksBeforeSubscriptionExpiryReminder" key="manager.subscriptionPolicies.expiryReminderBeforeWeeks2"}
</p>
<p>
	<input type="checkbox" name="enableSubscriptionExpiryReminderAfterWeeks" id="enableSubscriptionExpiryReminderAfterWeeks" value="1" onclick="toggleAllowSetAfterWeeksReminder(this.form)"{if !$scheduledTasksEnabled} disabled="disabled" {elseif $enableSubscriptionExpiryReminderAfterWeeks} checked="checked"{/if} />&nbsp;
	{fieldLabel name="enableSubscriptionExpiryReminderAfterWeeks" key="manager.subscriptionPolicies.expiryReminderAfterWeeks1"}
	<select name="numWeeksAfterSubscriptionExpiryReminder" id="numWeeksAfterSubscriptionExpiryReminder" class="selectMenu"{if not $enableSubscriptionExpiryReminderAfterWeeks || !$scheduledTasksEnabled} disabled="disabled"{/if}>{html_options options=$validWeeks selected=$numWeeksAfterSubscriptionExpiryReminder}</select>
	{fieldLabel name="numWeeksAfterSubscriptionExpiryReminder" key="manager.subscriptionPolicies.expiryReminderAfterWeeks2"}
</p>

{if !$scheduledTasksEnabled}
	<br/>
	{translate key="manager.subscriptionPolicies.expiryRemindersDisabled"}
{/if}
</div>

<div class="separator"></div>

<div id="onlinePaymentNotifications">
<h3>{translate key="manager.subscriptionPolicies.onlinePaymentNotifications"}</h3>
<p>{translate key="manager.subscriptionPolicies.onlinePaymentNotificationsDescription"}</p>
{if $journalPaymentsEnabled && $acceptSubscriptionPayments}
{assign var=paymentsEnabled value=true}
{/if}
<p>
	<input type="checkbox" name="enableSubscriptionOnlinePaymentNotificationPurchaseIndividual" id="enableSubscriptionOnlinePaymentNotificationPurchaseIndividual" value="1" {if !$paymentsEnabled} disabled="disabled" {elseif $enableSubscriptionOnlinePaymentNotificationPurchaseIndividual} checked="checked"{/if} />
	{fieldLabel name="enableSubscriptionOnlinePaymentNotificationPurchaseIndividual" key="manager.subscriptionPolicies.enableSubscriptionOnlinePaymentNotificationPurchaseIndividual"}
</p>
<p>
	<input type="checkbox" name="enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional" id="enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional" value="1" {if !$paymentsEnabled} disabled="disabled" {elseif $enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional} checked="checked"{/if} />
	{fieldLabel name="enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional" key="manager.subscriptionPolicies.enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional"}
</p>
<p>
	<input type="checkbox" name="enableSubscriptionOnlinePaymentNotificationRenewIndividual" id="enableSubscriptionOnlinePaymentNotificationRenewIndividual" value="1" {if !$paymentsEnabled} disabled="disabled" {elseif $enableSubscriptionOnlinePaymentNotificationRenewIndividual} checked="checked"{/if} />
	{fieldLabel name="enableSubscriptionOnlinePaymentNotificationRenewIndividual" key="manager.subscriptionPolicies.enableSubscriptionOnlinePaymentNotificationRenewIndividual"}
</p>
<p>
	<input type="checkbox" name="enableSubscriptionOnlinePaymentNotificationRenewInstitutional" id="enableSubscriptionOnlinePaymentNotificationRenewInstitutional" value="1" {if !$paymentsEnabled} disabled="disabled" {elseif $enableSubscriptionOnlinePaymentNotificationRenewInstitutional} checked="checked"{/if} />
	{fieldLabel name="enableSubscriptionOnlinePaymentNotificationRenewInstitutional" key="manager.subscriptionPolicies.enableSubscriptionOnlinePaymentNotificationRenewInstitutional"}
</p>
{translate key="manager.subscriptionPolicies.onlinePaymentPurchaseInstitutionalDescription"}
<br />
{if !$paymentsEnabled}
	<br />
	{translate key="manager.subscriptionPolicies.onlinePaymentDisabled"}
{/if}
</div>

<div class="separator"></div>

<div id="openAccessOptions">
<h3>{translate key="manager.subscriptionPolicies.openAccessOptions"}</h3>
<p>{translate key="manager.subscriptionPolicies.openAccessOptionsDescription"}</p>

	<h4>{translate key="manager.subscriptionPolicies.delayedOpenAccess"}</h4>
	<p>{translate key="manager.subscriptionPolicies.delayedOpenAccessDescription"}</p>
	<input type="checkbox" name="enableDelayedOpenAccess" id="enableDelayedOpenAccess" value="1" onclick="toggleAllowSetDelayedOpenAccessDuration(this.form)" {if $enableDelayedOpenAccess} checked="checked"{/if} />&nbsp;
	{fieldLabel name="enableDelayedOpenAccess" key="manager.subscriptionPolicies.delayedOpenAccessDescription1"}
	<select name="delayedOpenAccessDuration" id="delayedOpenAccessDuration" class="selectMenu" {if not $enableDelayedOpenAccess} disabled="disabled"{/if}>{html_options options=$validDuration selected=$delayedOpenAccessDuration}</select>
	{fieldLabel name="delayedOpenAccessDuration" key="manager.subscriptionPolicies.delayedOpenAccessDescription2"}

	<p>
	<input type="checkbox" name="enableOpenAccessNotification" id="enableOpenAccessNotification" value="1"{if !$scheduledTasksEnabled} disabled="disabled" {elseif $enableOpenAccessNotification} checked="checked"{/if} />&nbsp;
	{fieldLabel name="enableOpenAccessNotification" key="manager.subscriptionPolicies.openAccessNotificationDescription"}
	{if !$scheduledTasksEnabled}
		<br/>
		{translate key="manager.subscriptionPolicies.openAccessNotificationDisabled"}
	{/if}
	</p>

	<p>{translate key="manager.subscriptionPolicies.delayedOpenAccessPolicyDescription"}</p>
	<p>
	<textarea name="delayedOpenAccessPolicy[{$formLocale|escape}]" id="delayedOpenAccessPolicy" rows="12" cols="60" class="textArea">{$delayedOpenAccessPolicy[$formLocale]|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.subscriptionPolicies.htmlInstructions"}</span>
	</p>

	<h4>{translate key="manager.subscriptionPolicies.authorSelfArchive"}</h4>
<p>
	<input type="checkbox" name="enableAuthorSelfArchive" id="enableAuthorSelfArchive" value="1"{if $enableAuthorSelfArchive} checked="checked"{/if} />&nbsp;
	{fieldLabel name="enableAuthorSelfArchive" key="manager.subscriptionPolicies.authorSelfArchiveDescription"}
</p>
<p>
	<textarea name="authorSelfArchivePolicy[{$formLocale|escape}]" id="authorSelfArchivePolicy" rows="12" cols="60" class="textArea">{$authorSelfArchivePolicy[$formLocale]|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.subscriptionPolicies.htmlInstructions"}</span>
</p>
</div>

<div class="separator"></div>


<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="subscriptionPolicies" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}

