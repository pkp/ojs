{**
 * plugins/generic/timedView/timedViewReportForm.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to select dates for a timed view report
 *}
{assign var="pageTitle" value="plugins.reports.timedView.displayName"}
{include file="common/header.tpl"}

<br/>
{translate key="plugins.reports.timedView.form.largeSetOfDataIssue"}
<br/>
<br/>

<form method="post" action="{url path='TimedViewReportPlugin'}">
	{include file="common/formErrors.tpl"}

	<table class="data" width="100%">
	<tr valign="top">
		<td width="5%">
			<input type="checkbox" id="useTimedViewRecords" name="useTimedViewRecords" value=true />
		</td>
		<td width="95%">
			<label for="useTimedViewRecords">{translate key="plugins.reports.timedView.form.useTimedViewRecords"}</label>
		</td>		
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="dateStart" required="true" key="manager.subscriptions.form.dateStart"}</td>
		<td class="value" id="dateStart">{html_select_date prefix="dateStart" all_extra="class=\"selectMenu\"" start_year="$yearOffsetPast" end_year="$yearOffsetFuture" time="$dateStart"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="dateEnd" required="true" key="manager.subscriptions.form.dateEnd"}</td>
		<td class="value" id="dateEnd">
			{html_select_date prefix="dateEnd" start_year="$yearOffsetPast" all_extra="class=\"selectMenu\"" end_year="$yearOffsetFuture" time="$dateEnd"}
			<input type="hidden" name="dateEndHour" value="23" />
			<input type="hidden" name="dateEndMinute" value="59" />
			<input type="hidden" name="dateEndSecond" value="59" />
		</td>
	</tr>
	</table>

	<p>
		<input type="submit" value="{translate key="plugins.reports.timedView.form.generate"}" class="button defaultButton" />
		<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url path="TimedViewReportPlugin" escape=false}'" />
	</p>
	<input type="hidden" name="generate" value="1" />
</form>

<div class="separator"></div>

<h3>{translate key="plugins.reports.timedView.form.clearLogs"}</h3>
<form method="post" action="{url path='TimedViewReportPlugin'}">
	<p>{translate key="plugins.reports.timedView.form.clearLogs.description"}</p>

	{html_select_date prefix="dateClear" start_year="$yearOffsetPast" all_extra="class=\"selectMenu\"" end_year="$yearOffsetFuture" time="$dateEnd"}

	<p>
		<input type="submit" value="{translate key='plugins.reports.timedView.form.clearLogs'}" onclick="return confirm('{translate|escape:"jsparam" key="plugins.reports.timedView.form.clearLogs.confirm"}')" class="button defaultButton" />
	</p>
	<input type="hidden" name="clearLogs" value="1" />
</form>

{include file="common/footer.tpl"}
