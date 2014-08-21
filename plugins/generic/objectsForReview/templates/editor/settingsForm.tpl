{**
 * @file plugins/generic/objectsForReview/templates/editor/settingsForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Objects for Review plugin settings
 *
 *}
{assign var="pageTitle" value="plugins.generic.objectsForReview.settings"}
{include file="common/header.tpl"}

<ul class="menu">
	<li class="current"><a href="{url op="objectsForReview" path="all"}">{translate key="plugins.generic.objectsForReview.editor.assignments"}</a></li>
	<li><a href="{url op="objectsForReview"}">{translate key="plugins.generic.objectsForReview.editor.objectsForReview"}</a></li>
	<li class="current"><a href="{url op="objectsForReview"}">{translate key="plugins.generic.objectsForReview.settings"}</a></li>
</ul>

<br />

<form method="post" id="objectsForReviewSettingsForm" action="{url op="objectsForReviewSettings"}">
{include file="common/formErrors.tpl"}

<script type="text/javascript">
	{literal}
	<!--
		function toggleAllowSetBeforeDueReminder(form) {
			form.numDaysBeforeDueReminder.disabled = !form.numDaysBeforeDueReminder.disabled;
		}
		function toggleAllowSetAfterDueReminder(form) {
			form.numDaysAfterDueReminder.disabled = !form.numDaysAfterDueReminder.disabled;
		}
	// -->
	{/literal}
</script>

<div class="separator"></div>
<div id="objectsForReviewSettingsMode">
<h4>{translate key="plugins.generic.objectsForReview.settings.objectsForReviewMode"}</h4>
<br />
<table width="100%" class="data">
	<tr valign="top">
		<td width="10%" class="label" align="right"><input type="radio" name="mode" id="mode-1" value="{$smarty.const.OFR_MODE_FULL}" {if $mode eq "1"}checked="checked" {/if}/>&nbsp;</td>
		<td width="90%" class="value">{translate key="plugins.generic.objectsForReview.settings.modeFull"}</td>
	</tr>
	<tr valign="top">
		<td width="10%" class="label" align="right"><input type="radio" name="mode" id="mode-2" value="{$smarty.const.OFR_MODE_METADATA}" {if $mode eq "2"}checked="checked" {/if}/>&nbsp;</td>
		<td width="90%" class="value">{translate key="plugins.generic.objectsForReview.settings.modeMetadata"}</td>
	</tr>
	<tr>
		<td colspan="2">{translate key="plugins.generic.objectsForReview.settings.description"}</td>
	</tr>
</table>
</div>

<div class="separator"></div>
<div id="displayObjectMetadata">
<h4>{translate key="plugins.generic.objectsForReview.settings.displayMetadata"}</h4>
<p>
	<input type="checkbox" name="displayAbstract" id="displayAbstract" value="1" {if $displayAbstract} checked="checked"{/if} />&nbsp;
	{fieldLabel name="displayAbstract" key="plugins.generic.objectsForReview.settings.displayAbstract"}
</p>
<p>
	<input type="checkbox" name="displayListing" id="displayListing" value="1" {if $displayListing} checked="checked"{/if} />&nbsp;
	{fieldLabel name="displayAbstract" key="plugins.generic.objectsForReview.settings.displayListing"}
</p>
</div>

<div class="separator"></div>
<div id="objectsForReviewSettingsDue">
<h4>{translate key="plugins.generic.objectsForReview.settings.objectsForReviewDue"}</h4>
<p>
	{fieldLabel name="dueWeeks" key="plugins.generic.objectsForReview.settings.dueWeeks1"}&nbsp;<select name="dueWeeks" id="dueWeeks" class="selectMenu">{html_options options=$validDueWeeks selected=$dueWeeks}</select>&nbsp;{translate key="plugins.generic.objectsForReview.settings.dueWeeks2"}
</p>
</div>

<div class="separator"></div>
<div id="objectsForReviewSettingsEmailReminders">
<h4>{translate key="plugins.generic.objectsForReview.settings.emailReminders"}</h4>
<p>
	<input type="checkbox" name="enableDueReminderBefore" id="enableDueReminderBefore" value="1" onclick="toggleAllowSetBeforeDueReminder(this.form)"{if !$scheduledTasksEnabled} disabled="disabled" {elseif $enableDueReminderBefore} checked="checked"{/if} />&nbsp;
	{fieldLabel name="enableDueReminderBefore" key="plugins.generic.objectsForReview.settings.enableDueReminderBeforeDays1"}
	<select name="numDaysBeforeDueReminder" id="numDaysBeforeDueReminder" class="selectMenu"{if not $enableDueReminderBefore || !$scheduledTasksEnabled} disabled="disabled"{/if}>{html_options options=$validNumDays selected=$numDaysBeforeDueReminder}</select>
	{fieldLabel name="numDaysBeforeDueReminder" key="plugins.generic.objectsForReview.settings.enableDueReminderBeforeDays2"}
</p>
<p>
	<input type="checkbox" name="enableDueReminderAfter" id="enableDueReminderAfter" value="1" onclick="toggleAllowSetAfterDueReminder(this.form)"{if !$scheduledTasksEnabled} disabled="disabled" {elseif $enableDueReminderAfter} checked="checked"{/if} />&nbsp;
	{fieldLabel name="enableDueReminderAfter" key="plugins.generic.objectsForReview.settings.enableDueReminderAfterDays1"}
	<select name="numDaysAfterDueReminder" id="numDaysAfterDueReminder" class="selectMenu"{if not $enableDueReminderAfter || !$scheduledTasksEnabled} disabled="disabled"{/if}>{html_options options=$validNumDays selected=$numDaysAfterDueReminder}</select>
	{fieldLabel name="numDaysAfterDueReminder" key="plugins.generic.objectsForReview.settings.enableDueReminderAfterDays2"}
</p>

{if !$scheduledTasksEnabled}
	<br/>
	{translate key="plugins.generic.objectsForReview.settings.scheduledTasksDisabled"}
{/if}
</div>

<div class="separator"></div>
<div id="objectsForReviewSettingsAdditionalInformation">
<h4>{translate key="plugins.generic.objectsForReview.settings.additionalInformation"}</h4>
<p>{translate key="plugins.generic.objectsForReview.settings.additionalInformationDescription"}</p>
<table width="100%" class="data">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="100%" class="value">
			{url|assign:"objectsForReviewSettingsFormUrl" op="objectsForReviewSettings" escape=false}
			{form_language_chooser form="objectsForReviewSettingsForm" url=$objectsForReviewSettingsFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
	<tr valign="top">
		<td width="100%" class="value"><textarea name="additionalInformation[{$formLocale|escape}]" id="additionalInformation" rows="6" cols="60" class="textArea">{$additionalInformation[$formLocale]|escape}</textarea></td>
	</tr>
</table>
</div>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/>&nbsp;<input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
