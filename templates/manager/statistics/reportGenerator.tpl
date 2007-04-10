{**
 * reportGenerator.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the report generator.
 *
 * $Id$
 *}

<a name="statistics"></a>
<h3>{translate key="manager.statistics.reports"}</h3>
<p>{translate key="manager.statistics.reports.description"}</p>

<form method="post" action="{url op="reportGenerator"}">
<table width="100%" class="data">
	<tr valign="top">
		<td width="25%" class="label">{translate key="manager.statistics.reports.reportType"}</td>
		<td width="75%" class="value"><select name="reportType" id="reportType" class="selectMenu">{html_options_translate options=$reportTypes}</select></td>
	</td>
	<tr valign="top">
		<td class="label">{translate key="search.dateFrom"}</td>
		<td class="value">{html_select_date prefix="dateFrom" time="--" all_extra="class=\"selectMenu\"" year_empty="" month_empty="" day_empty="" start_year="-5" end_year="+1"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="search.dateTo"}</td>
		<td class="value">{html_select_date prefix="dateTo" time="--" all_extra="class=\"selectMenu\"" year_empty="" month_empty="" day_empty="" start_year="-5" end_year="+1"}</td>
	</tr>
</table>

<input type="submit" class="button defaultButton" value="{translate key="manager.statistics.reports.generate"}"/>

</form>
