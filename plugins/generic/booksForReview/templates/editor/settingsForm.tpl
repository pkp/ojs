{**
 * @file plugins/generic/booksForReview/templates/editor/settingsForm.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Books for Review plugin settings
 *
 *}
{assign var="pageTitle" value="plugins.generic.booksForReview.booksForReviewSettings"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{url op="booksForReview"}">{translate key="plugins.generic.booksForReview.editor.all"}</a></li>
	<li><a href="{url op="booksForReview" path="available"}">{translate key="plugins.generic.booksForReview.editor.available"} ({$counts[$smarty.const.BFR_STATUS_AVAILABLE]})</a></li>
	{if $mode == $smarty.const.BFR_MODE_FULL}
		<li><a href="{url op="booksForReview" path="requested"}">{translate key="plugins.generic.booksForReview.editor.requested"} ({$counts[$smarty.const.BFR_STATUS_REQUESTED]})</a></li>
		<li><a href="{url op="booksForReview" path="assigned"}">{translate key="plugins.generic.booksForReview.editor.assigned"} ({$counts[$smarty.const.BFR_STATUS_ASSIGNED]})</a></li>
		<li><a href="{url op="booksForReview" path="mailed"}">{translate key="plugins.generic.booksForReview.editor.mailed"} ({$counts[$smarty.const.BFR_STATUS_MAILED]})</a></li>
	{/if}
	<li><a href="{url op="booksForReview" path="submitted"}">{translate key="plugins.generic.booksForReview.editor.submitted"} ({$counts[$smarty.const.BFR_STATUS_SUBMITTED]})</a></li>
	<li class="current"><a href="{url op="booksForReviewSettings"}">{translate key="plugins.generic.booksForReview.settings"}</a></li>
</ul>

<br />

<form method="post" id="booksForReviewSettingsForm" action="{url op="booksForReviewSettings"}">
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

<div id="booksForReviewMode">

<h4>{translate key="plugins.generic.booksForReview.settings.booksForReviewMode"}</h4>
<br />
<table width="100%" class="data">
	<tr valign="top">
		<td width="10%" class="label" align="right"><input type="radio" name="mode" id="mode-1" value="{$smarty.const.BFR_MODE_FULL}" {if $mode eq "1"}checked="checked" {/if}/>&nbsp;</td>
		<td width="90%" class="value">{translate key="plugins.generic.booksForReview.settings.modeFull"}</td>
	</tr>
	<tr valign="top">
		<td width="10%" class="label" align="right"><input type="radio" name="mode" id="mode-2" value="{$smarty.const.BFR_MODE_METADATA}" {if $mode eq "2"}checked="checked" {/if}/>&nbsp;</td>
		<td width="90%" class="value">{translate key="plugins.generic.booksForReview.settings.modeMetadata"}</td>
	</tr>
	<tr>
		<td colspan="2">{translate key="plugins.generic.booksForReview.settings.description"}</td>
	</tr>
</table>
</div>

<div class="separator"></div>

<div id="booksForReviewCoverImages">
<h4>{translate key="plugins.generic.booksForReview.settings.coverImages"}</h4>
<p>
	<input type="checkbox" name="coverPageIssue" id="coverPageIssue" value="1" {if $coverPageIssue} checked="checked"{/if} />&nbsp;
	{fieldLabel name="coverPageIssue" key="plugins.generic.booksForReview.settings.coverPageIssue"}
</p>
<p>
	<input type="checkbox" name="coverPageAbstract" id="coverPageAbstract" value="1" {if $coverPageAbstract} checked="checked"{/if} />&nbsp;
	{fieldLabel name="coverPageAbstract" key="plugins.generic.booksForReview.settings.coverPageAbstract"}
</p>
</div>

<div class="separator"></div>

<div id="booksForReviewDue">
<h4>{translate key="plugins.generic.booksForReview.settings.booksForReviewDue"}</h4>
<p>
	{fieldLabel name="dueWeeks" key="plugins.generic.booksForReview.settings.dueWeeks1"}&nbsp;<select name="dueWeeks" id="dueWeeks" class="selectMenu">{html_options options=$validDueWeeks selected=$dueWeeks|escape}</select>&nbsp;{translate key="plugins.generic.booksForReview.settings.dueWeeks2}
</p>
</div>

<div class="separator"></div>

<div id="booksForReviewEmailReminders">
<h4>{translate key="plugins.generic.booksForReview.settings.emailReminders"}</h4>
<p>
	<input type="checkbox" name="enableDueReminderBefore" id="enableDueReminderBefore" value="1" onclick="toggleAllowSetBeforeDueReminder(this.form)"{if !$scheduledTasksEnabled} disabled="disabled" {elseif $enableDueReminderBefore} checked="checked"{/if} />&nbsp;
	{fieldLabel name="enableDueReminderBefore" key="plugins.generic.booksForReview.settings.enableDueReminderBeforeDays1"}
	<select name="numDaysBeforeDueReminder" id="numDaysBeforeDueReminder" class="selectMenu"{if not $enableDueReminderBefore || !$scheduledTasksEnabled} disabled="disabled"{/if}>{html_options options=$validNumDays selected=$numDaysBeforeDueReminder}</select>
	{fieldLabel name="numDaysBeforeDueReminder" key="plugins.generic.booksForReview.settings.enableDueReminderBeforeDays2"}
</p>
<p>
	<input type="checkbox" name="enableDueReminderAfter" id="enableDueReminderAfter" value="1" onclick="toggleAllowSetAfterDueReminder(this.form)"{if !$scheduledTasksEnabled} disabled="disabled" {elseif $enableDueReminderAfter} checked="checked"{/if} />&nbsp;
	{fieldLabel name="enableDueReminderAfter" key="plugins.generic.booksForReview.settings.enableDueReminderAfterDays1"}
	<select name="numDaysAfterDueReminder" id="numDaysAfterDueReminder" class="selectMenu"{if not $enableDueReminderAfter || !$scheduledTasksEnabled} disabled="disabled"{/if}>{html_options options=$validNumDays selected=$numDaysAfterDueReminder}</select>
	{fieldLabel name="numDaysAfterDueReminder" key="plugins.generic.booksForReview.settings.enableDueReminderAfterDays2"}
</p>

{if !$scheduledTasksEnabled}
	<br/>
	{translate key="plugins.generic.booksForReview.settings.scheduledTasksDisabled"}
{/if}
</div>

<div class="separator"></div>

<div id="booksForReviewadditionalInformation">
<h4>{translate key="plugins.generic.booksForReview.settings.additionalInformation"}</h4>
<p>{translate key="plugins.generic.booksForReview.settings.additionalInformationDescription"}</p>
<table width="100%" class="data">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="100%" class="value">
			{url|assign:"settingsUrl" op="booksForReviewSettings"}
			{form_language_chooser form="booksForReviewSettingsForm" url=$settingsUrl}
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
