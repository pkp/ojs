{**
 * subscriptionPolicyForm.tpl
 *
 * Copyright (c) 2003-2006 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Setup subscription policies.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.subscriptionPolicies"}
{assign var="pageId" value="manager.subscriptionPolicies"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{url op="subscriptions"}">{translate key="manager.subscriptions"}</a></li>
	<li><a href="{url op="subscriptionTypes"}">{translate key="manager.subscriptionTypes"}</a></li>
	<li class="current"><a href="{url op="subscriptionPolicies"}">{translate key="manager.subscriptionPolicies"}</a></li>
</ul>

{if $subscriptionPoliciesSaved}
<br/>
{translate key="manager.subscriptionPolicies.subscriptionPoliciesSaved"}<br />
{/if}

<form method="post" action="{url op="saveSubscriptionPolicies"}">
{include file="common/formErrors.tpl"}

<h3>{translate key="manager.subscriptionPolicies.openAccessOptions"}</h3>
<p>{translate key="manager.subscriptionPolicies.openAccessOptionsDescription"}</p>

	<h4>{translate key="manager.subscriptionPolicies.delayedOpenAccess"}</h4>
	<input type="checkbox" name="enableDelayedOpenAccess" id="enableDelayedOpenAccess" value="1"{if $enableDelayedOpenAccess} checked="checked"{/if} />&nbsp;
	<label for="enableDelayedOpenAccess">{translate key="manager.subscriptionPolicies.delayedOpenAccessDescription1"}</label>
	<select name="delayedOpenAccessDuration" id="delayedOpenAccessDuration" class="selectMenu" />{html_options options=$validDuration selected=$delayedOpenAccessDuration}</select>
	{translate key="manager.subscriptionPolicies.delayedOpenAccessDescription2"}


<p>
	<h4>{translate key="manager.subscriptionPolicies.authorSelfArchive"}</h4>
	<input type="checkbox" name="enableAuthorSelfArchive" id="enableAuthorSelfArchive" value="1"{if $enableAuthorSelfArchive} checked="checked"{/if} />&nbsp;
	<label for="enableAuthorSelfArchive">{translate key="manager.subscriptionPolicies.authorSelfArchiveDescription"}</label>
</p>
<p>
	<textarea name="authorSelfArchivePolicy" id="authorSelfArchivePolicy" rows="12" cols="60" class="textArea">{$authorSelfArchivePolicy|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.subscriptionPolicies.htmlInstructions"}</span>
</p>


<div class="separator"></div>

<h3>{translate key="manager.subscriptionPolicies.subscriptionAdditionalInformation"}</h3>
<p>{translate key="manager.subscriptionPolicies.subscriptionAdditionalInformationDescription"}</p>
<p>
	<textarea name="subscriptionAdditionalInformation" id="subscriptionAdditionalInformation" rows="12" cols="60" class="textArea">{$subscriptionAdditionalInformation|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.subscriptionPolicies.htmlInstructions"}</span>
</p>

<div class="separator"></div>

<h3>{translate key="manager.subscriptionPolicies.expiryReminders"}</h3>
<p>{translate key="manager.subscriptionPolicies.expiryRemindersDescription"}</p>

	<script type="text/javascript">
		{literal}
		<!--
			function toggleAllowSetBeforeMonthsReminder(form) {
				form.numMonthsBeforeSubscriptionExpiryReminder.disabled = !form.numMonthsBeforeSubscriptionExpiryReminder.disabled;
			}
			function toggleAllowSetBeforeWeeksReminder(form) {
				form.numWeeksBeforeSubscriptionExpiryReminder.disabled = !form.numWeeksBeforeSubscriptionExpiryReminder.disabled;
			}
			function toggleAllowSetAfterMonthsReminder(form) {
				form.numMonthsAfterSubscriptionExpiryReminder.disabled = !form.numMonthsAfterSubscriptionExpiryReminder.disabled;
			}
			function toggleAllowSetAfterWeeksReminder(form) {
				form.numWeeksAfterSubscriptionExpiryReminder.disabled = !form.numWeeksAfterSubscriptionExpiryReminder.disabled;
			}
		// -->
		{/literal}
	</script>

<p>
	<input type="checkbox" name="enableSubscriptionExpiryReminderBeforeMonths" id="enableSubscriptionExpiryReminderBeforeMonths" value="1" onclick="toggleAllowSetBeforeMonthsReminder(this.form)"{if !$scheduledTasksEnabled} disabled="disabled" {elseif $enableSubscriptionExpiryReminderBeforeMonths} checked="checked"{/if} />&nbsp;
	<label for="enableSubscriptionExpiryReminderBeforeMonths">{translate key="manager.subscriptionPolicies.expiryReminderBeforeMonths1"}</label>
	<select name="numMonthsBeforeSubscriptionExpiryReminder" id="numMonthsBeforeSubscriptionExpiryReminder" class="selectMenu"{if not $enableSubscriptionExpiryReminderBeforeMonths || !$scheduledTasksEnabled} disabled="disabled"{/if} />{html_options options=$validNumMonthsBeforeExpiry selected=$numMonthsBeforeSubscriptionExpiryReminder}</select>
	{translate key="manager.subscriptionPolicies.expiryReminderBeforeMonths2"}
</p>
<p>
	<input type="checkbox" name="enableSubscriptionExpiryReminderBeforeWeeks" id="enableSubscriptionExpiryReminderBeforeWeeks" value="1" onclick="toggleAllowSetBeforeWeeksReminder(this.form)"{if !$scheduledTasksEnabled} disabled="disabled" {elseif $enableSubscriptionExpiryReminderBeforeWeeks} checked="checked"{/if} />&nbsp;
	<label for="enableSubscriptionExpiryReminderBeforeWeeks">{translate key="manager.subscriptionPolicies.expiryReminderBeforeWeeks1"}</label>
	<select name="numWeeksBeforeSubscriptionExpiryReminder" id="numWeeksBeforeSubscriptionExpiryReminder" class="selectMenu"{if not $enableSubscriptionExpiryReminderBeforeWeeks || !$scheduledTasksEnabled} disabled="disabled"{/if} />{html_options options=$validNumWeeksBeforeExpiry selected=$numWeeksBeforeSubscriptionExpiryReminder}</select>
	{translate key="manager.subscriptionPolicies.expiryReminderBeforeWeeks2"}
</p>
<p>
	<input type="checkbox" name="enableSubscriptionExpiryReminderAfterWeeks" id="enableSubscriptionExpiryReminderAfterWeeks" value="1" onclick="toggleAllowSetAfterWeeksReminder(this.form)"{if !$scheduledTasksEnabled} disabled="disabled" {elseif $enableSubscriptionExpiryReminderAfterWeeks} checked="checked"{/if} />&nbsp;
	<label for="enableSubscriptionExpiryReminderAfterWeeks">{translate key="manager.subscriptionPolicies.expiryReminderAfterWeeks1"}</label>
	<select name="numWeeksAfterSubscriptionExpiryReminder" id="numWeeksAfterSubscriptionExpiryReminder" class="selectMenu"{if not $enableSubscriptionExpiryReminderAfterWeeks || !$scheduledTasksEnabled} disabled="disabled"{/if} />{html_options options=$validNumWeeksAfterExpiry selected=$numWeeksAfterSubscriptionExpiryReminder}</select>
	{translate key="manager.subscriptionPolicies.expiryReminderAfterWeeks2"}
</p>
<p>
	<input type="checkbox" name="enableSubscriptionExpiryReminderAfterMonths" id="enableSubscriptionExpiryReminderAfterMonths" value="1" onclick="toggleAllowSetAfterMonthsReminder(this.form)"{if !$scheduledTasksEnabled} disabled="disabled" {elseif $enableSubscriptionExpiryReminderAfterMonths} checked="checked"{/if} />&nbsp;
	<label for="enableSubscriptionExpiryReminderAfterMonths">{translate key="manager.subscriptionPolicies.expiryReminderAfterMonths1"}</label>
	<select name="numMonthsAfterSubscriptionExpiryReminder" id="numMonthsAfterSubscriptionExpiryReminder" class="selectMenu"{if not $enableSubscriptionExpiryReminderAfterMonths || !$scheduledTasksEnabled} disabled="disabled"{/if} />{html_options options=$validNumMonthsAfterExpiry selected=$numMonthsAfterSubscriptionExpiryReminder}</select>
	{translate key="manager.subscriptionPolicies.expiryReminderAfterMonths2"}
</p>

{if !$scheduledTasksEnabled}
	<br/>
	{translate key="manager.subscriptionPolicies.expiryRemindersDisabled"}
{/if}

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="subscriptionPolicies" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
