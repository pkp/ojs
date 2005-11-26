{**
 * reportGenerator.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the report generator.
 *
 * $Id$
 *}

<a name="statistics"></a>
<h3>{translate key="manager.statistics.reports"}</h3>

<form method="post" action="{url op="generateReport"}">
<table width="100%" class="data">
	<tr valign="top">
		<td class="label">{translate key="search.dateFrom"}</td>
		<td class="value">{html_select_date prefix="dateFrom" time="--" all_extra="class=\"selectMenu\"" year_empty="" month_empty="" day_empty="" start_year="-5" end_year="+1"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="search.dateTo"}</td>
		<td class="value">{html_select_date prefix="dateTo" time="--" all_extra="class=\"selectMenu\"" year_empty="" month_empty="" day_empty="" start_year="-5" end_year="+1"}</td>
	</tr>
	<tr valign="top">
		<td class="label" colspan="2">
			{translate key="manager.statistics.reports.includeInReportType"}&nbsp;<select name="reportType" id="reportType" class="selectMenu">{html_options_translate options=$reportTypes}</select>
		</td>
	</td>
</table>

<input type="checkbox" name="authors" value="1" />&nbsp;{translate key="article.authors"}<br />
<input type="checkbox" name="affiliations" value="1" />&nbsp;{translate key="user.affiliations"}<br />
<input type="checkbox" name="authors" value="1" />&nbsp;{translate key="article.title"}<br />
<input type="checkbox" name="authors" value="1" />&nbsp;{translate key="section.section"}<br />
<input type="checkbox" name="authors" value="1" />&nbsp;{translate key="common.dateSubmitted"}<br />
<input type="checkbox" name="authors" value="1" />&nbsp;{translate key="user.role.editor"}<br />
<input type="checkbox" name="authors" value="1" />&nbsp;{translate key="user.role.reviewers"}<br />
<input type="checkbox" name="authors" value="1" />&nbsp;{translate key="manager.statistics.reports.status"}<br />
<input type="checkbox" name="authors" value="1" />&nbsp;{translate key="manager.statistics.reports.dateDecided"}<br />
<br />

<input type="submit" class="button defaultButton" value="{translate key="manager.statistics.reports.generate"}"/>

</form>
