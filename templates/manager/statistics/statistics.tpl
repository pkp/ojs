{**
 * statistics.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the statistics table.
 *
 * $Id$
 *}

{* WARNING: This page should be kept roughly synchronized with the
   implementation of reader statistics in the About page.          *}
<a name="statistics"></a>
<h3>{translate key="manager.statistics.statistics"}</h3>
<p>{translate key="manager.statistics.statistics.description"}</p>

<p>{translate key="manager.statistics.statistics.selectSections"}</p>
<form action="{url op="saveStatisticsSections"}" method="post">
	<select name="sectionIds[]" class="selectMenu" multiple size="5">
		{foreach from=$sections item=section}
			<option {if in_array($section->getSectionId(), $sectionIds)}selected {/if}value="{$section->getSectionId()}">{$section->getSectionTitle()}</option>
		{/foreach}
	</select><br/>&nbsp;<br/>
	<input type="submit" value="{translate key="common.record"}" class="button defaultButton"/>
</form>

<br/>

<form action="{url op="savePublicStatisticsList"}" method="post">
<table width="100%" class="data">
	<tr valign="top">
		<td width="25%" class="label"><h4>{translate key="common.year"}</h4></td>
		<td width="75%" colspan="2" class="value">
			<h4><a class="action" href="{url statisticsYear=$statisticsYear-1}">{translate key="navigation.previousPage"}</a>&nbsp;{$statisticsYear}&nbsp;<a class="action" href="{url statisticsYear=$statisticsYear+1}">{translate key="navigation.nextPage"}</a></h4>
		</td>
	</tr>

	<tr valign="top">
		<td class="label"><input type="checkbox" name="statNumPublishedIssues" {if $statNumPublishedIssues}checked {/if}/>{translate key="manager.statistics.statistics.numIssues"}</td>
		<td colspan="2" class="value">{$issueStatistics.numPublishedIssues}</td>
	</tr>

	<tr valign="top">
		<td width="20%" class="label"><input type="checkbox" name="statItemsPublished" {if $statItemsPublished}checked {/if}/>{translate key="manager.statistics.statistics.itemsPublished"}</td>
		<td width="80%" colspan="2" class="value">{$articleStatistics.numPublishedSubmissions}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="checkbox" name="statNumSubmissions" {if $statNumSubmissions}checked {/if}/>{translate key="manager.statistics.statistics.numSubmissions"}</td>
		<td width="80%" colspan="2" class="value">{$articleStatistics.numSubmissions}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="checkbox" name="statPeerReviewed" {if $statPeerReviewed}checked {/if}/>{translate key="manager.statistics.statistics.peerReviewed"}</td>
		<td width="80%" colspan="2" class="value">{$reviewerStatistics.reviewedSubmissionsCount}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="checkbox" name="statCountAccept" {if $statCountAccept}checked {/if}/>&nbsp;&nbsp;{translate key="manager.statistics.statistics.count.accept"}</td>
		<td width="80%" colspan="2" class="value">{translate key="manager.statistics.statistics.count.value" count=$limitedArticleStatistics.submissionsAccept percentage=$limitedArticleStatistics.submissionsAcceptPercent}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="checkbox" name="statCountDecline" {if $statCountDecline}checked {/if}/>&nbsp;&nbsp;{translate key="manager.statistics.statistics.count.decline"}</td>
		<td width="80%" colspan="2" class="value">{translate key="manager.statistics.statistics.count.value" count=$limitedArticleStatistics.submissionsDecline percentage=$limitedArticleStatistics.submissionsDeclinePercent}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="checkbox" name="statCountRevise" {if $statCountRevise}checked {/if}/>&nbsp;&nbsp;{translate key="manager.statistics.statistics.count.revise"}</td>
		<td width="80%" colspan="2" class="value">{translate key="manager.statistics.statistics.count.value" count=$limitedArticleStatistics.submissionsRevise percentage=$limitedArticleStatistics.submissionsRevisePercent}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="checkbox" name="statDaysPerReview" {if $statDaysPerReview}checked {/if}/>&nbsp;&nbsp;{translate key="manager.statistics.statistics.daysPerReview"}</td>
		<td colspan="2" class="value">
			{assign var=daysPerReview value=$reviewerStatistics.daysPerReview}
			{math equation="round($daysPerReview)"}
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="checkbox" name="statDaysToPublication" {if $statDaysToPublication}checked {/if}/>&nbsp;&nbsp;{translate key="manager.statistics.statistics.daysToPublication"}</td>
		<td colspan="2" class="value">{$limitedArticleStatistics.daysToPublication}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="checkbox" name="statRegisteredUsers" {if $statRegisteredUsers}checked {/if}/>{translate key="manager.statistics.statistics.registeredUsers"}</td>
		<td colspan="2" class="value">{translate key="manager.statistics.statistics.totalNewValue" numTotal=$allUserStatistics.totalUsersCount numNew=$userStatistics.totalUsersCount}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="checkbox" name="statRegisteredReaders" {if $statRegisteredReaders}checked {/if}/>{translate key="manager.statistics.statistics.registeredReaders"}</td>
		<td colspan="2" class="value">{translate key="manager.statistics.statistics.totalNewValue" numTotal=$allUserStatistics.reader|default:"0" numNew=$userStatistics.reader|default:"0"}</td>
	</tr>

	{if $enableSubscriptions}
		<tr valign="top">
			<td colspan="3" class="label"><input type="checkbox" name="statSubscriptions" {if $statSubscriptions}checked {/if}/>{translate key="manager.statistics.statistics.subscriptions"}</td>
		</tr>
		{foreach from=$allSubscriptionStatistics key=type_id item=stats}
		<tr valign="top">
			<td width="20%" class="label">&nbsp;&nbsp;{$stats.name}:</td>
			<td colspan="2" class="value">{translate key="manager.statistics.statistics.totalNewValue" numTotal=$stats.count|default:"0" numNew=$subscriptionStatistics.$type_id.count|default:"0"}</td>
		</tr>
		{/foreach}
	{/if}

	<tr valign="top">
		<td width="20%" colspan="3" class="label"><input type="checkbox" name="statViews" {if $statViews}checked {/if}/>{translate key="manager.statistics.statistics.articleViews"}</td>
	</tr>
</table>
<p>{translate key="manager.statistics.statistics.note"}</p>

{translate key="manager.statistics.statistics.makePublic"}<br/>
<input type="submit" class="button defaultButton" value="{translate key="common.record"}"/>
</form>
