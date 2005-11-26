{**
 * statistics.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the statistics table.
 *
 * $Id$
 *}

<a name="statistics"></a>
<h3>{translate key="manager.statistics.statistics"}</h3>

<table width="100%" class="data">

	{* Submission statistics *}
	<tr valign="top">
		<td width="20%" class="label">{translate key="manager.statistics.statistics.numSubmissions"}</td>
		<td width="80%" colspan="2" class="value">
			{translate key="manager.statistics.statistics.numSubmissionsValue" numSubmissions=$articleStatistics.numSubmissions numPublishedSubmissions=$articleStatistics.numPublishedSubmissions}
		</td>
	</tr>

	{* Issue statistics *}
	<tr valign="top">
		<td width="20%" class="label">{translate key="manager.statistics.statistics.numIssues"}</td>
		<td width="80%" colspan="2" class="value">
			{translate key="manager.statistics.statistics.numIssuesValue" numIssues=$issueStatistics.numIssues numPublishedIssues=$issueStatistics.numPublishedIssues}
		</td>
	</tr>

	{* User statistics *}
	<tr valign="top">
		<td width="20%" rowspan="3" class="label">{translate key="manager.statistics.statistics.users"}</td>
		<td width="20%" class="value">
			{translate key="manager.statistics.statistics.totalUsers"}
		</td>
		<td width="60%" class="value">
			{translate key="manager.statistics.statistics.totalUsersValue" numUsers=$userStatistics.totalUsersCount numNotifiableUsers=$notifiableUsers}
		</td>
	</tr>
	<tr valign="top">
		<td class="value">{translate key="user.role.authors"}</td>
		<td class="value">{$userStatistics.author}</td>
	</tr>
	<tr valign="top">
		<td class="value">{translate key="user.role.reviewers"}</td>
		<td class="value">{$userStatistics.reviewer}</td>
	</tr>
</table>
