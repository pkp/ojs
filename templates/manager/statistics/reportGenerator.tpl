{**
 * templates/manager/statistics/reportGenerator.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the report generator.
 *
 *}
<div id="statistics">
<h3>{translate key="manager.statistics.reports"}</h3>
<p>{translate key="manager.statistics.reports.description"}</p>
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#reportForm').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="reportForm" method="post" action="{url op="reportGenerator"}">
{csrf}
<table class="data">
	<tr>
		<td class="label">{translate key="manager.statistics.reports.reportType"}</td>
		<td class="value"><select name="reportType" id="reportType" class="selectMenu">{html_options_translate options=$reportTypes}</select></td>
	</tr>
	<tr>
		<td class="label">{translate key="search.dateFrom"}</td>
		<td class="value">{html_select_date prefix="dateFrom" time="--" all_extra="class=\"selectMenu\"" year_empty="" month_empty="" day_empty="" start_year="-5" end_year="+1"}</td>
	</tr>
	<tr>
		<td class="label">{translate key="search.dateTo"}</td>
		<td class="value">
			{html_select_date prefix="dateTo" time="--" all_extra="class=\"selectMenu\"" year_empty="" month_empty="" day_empty="" start_year="-5" end_year="+1"}
			<input type="hidden" name="dateToHour" value="23" />
			<input type="hidden" name="dateToMinute" value="59" />
			<input type="hidden" name="dateToSecond" value="59" />
		</td>
	</tr>
</table>

<input type="submit" class="button defaultButton" value="{translate key="manager.statistics.reports.generate"}"/>

</form>
</div>
