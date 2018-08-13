{**
 * templates/manager/statistics/statistics.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the statistics table.
 *
 *}
{* WARNING: This page should be kept roughly synchronized with the
   implementation of reader statistics in the About page.          *}
<div id="statistics">
	<form class="pkp_form" id="saveSectionsForm" action="{url op="saveStatisticsSettings"}" method="post">
		{csrf}
		{if count($availableMetricTypes) > 1}
			<h3>{translate key="defaultMetric.title"}</h3>
			<p>{translate key="manager.statistics.defaultMetricDescription"}</p>
			<div id="defaultMetricSelection">
				<table class="data" width="100%">
					<tr valign="top">
						<td width="20%" class="label">{fieldLabel name="defaultMetricType" key="defaultMetric.availableMetrics"}</td>
						<td colspan="2" width="80%" class="value">
							<select name="defaultMetricType" class="selectMenu" id="defaultMetricType">
								{foreach from=$availableMetricTypes key=metricType item=displayName}
									<option value="{$metricType|escape}"{if $metricType == $defaultMetricType} selected="selected"{/if}>{$displayName|escape}</option>
								{/foreach}
							</select>
						</td>
					</tr>
				</table>
			</div>
			<br />
		{/if}
	
		<h3>{translate key="manager.statistics.statistics"}</h3>
		<p>{translate key="manager.statistics.statistics.description"}</p>
		<div id="selectSections">
			<p>{translate key="manager.statistics.statistics.selectSections"}</p>
			<script type="text/javascript">
				$(function() {ldelim}
					// Attach the form handler.
					$('#saveSectionsForm').pkpHandler('$.pkp.controllers.form.FormHandler');
				{rdelim});
			</script>
				<select name="sectionIds[]" class="selectMenu" multiple="multiple" size="5">
					{foreach from=$sections item=section}
						<option {if in_array($section->getId(), $sectionIds)}selected="selected" {/if}value="{$section->getId()}">{$section->getLocalizedTitle()}</option>
					{/foreach}
				</select><br/>&nbsp;<br/>
				<input type="submit" value="{translate key="common.record"}" class="button defaultButton"/>
		</div>
	</form>
	<br/>
	
	<script type="text/javascript">
		$(function() {ldelim}
			// Attach the form handler.
			$('#saveStatsListForm').pkpHandler('$.pkp.controllers.form.FormHandler');
		{rdelim});
	</script>
	<form class="pkp_form" id="saveStatsListForm" action="{url op="savePublicStatisticsList"}" method="post">
		{csrf}
		<table width="100%" class="data">
			<tr valign="top">
				<td width="25%" class="label"><h4>{translate key="common.year"}</h4></td>
				<td width="75%" colspan="2" class="value">
					<h4><a class="action" href="{url statisticsYear=$statisticsYear-1}">{translate key="navigation.previousPage"}</a>&nbsp;{$statisticsYear|escape}&nbsp;<a class="action" href="{url statisticsYear=$statisticsYear+1}">{translate key="navigation.nextPage"}</a></h4>
				</td>
			</tr>
		
			<tr valign="top">
		<td class="label"><input type="checkbox" id="statNumPublishedIssues" name="statNumPublishedIssues" {if $statNumPublishedIssues}checked="checked" {/if}/><label for="statNumPublishedIssues">{translate key="manager.statistics.statistics.numIssues"}</label></td>
		<td colspan="2" class="value">{$issueStatistics.numPublishedIssues}</td>
	</tr>

	<tr>
		<td class="label"><input type="checkbox" id="statItemsPublished" name="statItemsPublished" {if $statItemsPublished}checked="checked" {/if}/><label for="statItemsPublished">{translate key="manager.statistics.statistics.itemsPublished"}</label></td>
		<td colspan="2" class="value">{$articleStatistics.numPublishedSubmissions}</td>
			</tr>
	<tr>
		<td class="label"><input type="checkbox" id="statNumSubmissions" name="statNumSubmissions" {if $statNumSubmissions}checked="checked" {/if}/><label for="statNumSubmissions">{translate key="manager.statistics.statistics.numSubmissions"}</label></td>
		<td colspan="2" class="value">{$articleStatistics.numSubmissions}</td>
			</tr>
	<tr>
		<td class="label"><input type="checkbox" id="statPeerReviewed" name="statPeerReviewed" {if $statPeerReviewed}checked="checked" {/if}/><label for="statPeerReviewed">{translate key="manager.statistics.statistics.peerReviewed"}</label></td>
		<td colspan="2" class="value">{$limitedArticleStatistics.numReviewedSubmissions}</td>
			</tr>
	<tr>
		<td class="label"><input type="checkbox" id="statCountAccept" name="statCountAccept" {if $statCountAccept}checked="checked" {/if}/>&nbsp;&nbsp;<label for="statCountAccept">{translate key="manager.statistics.statistics.count.accept"}</label></td>
		<td colspan="2" class="value">{translate key="manager.statistics.statistics.count.value" count=$limitedArticleStatistics.submissionsAccept percentage=$limitedArticleStatistics.submissionsAcceptPercent}</td>
			</tr>
	<tr>
		<td class="label"><input type="checkbox" id="statCountDecline" name="statCountDecline" {if $statCountDecline}checked="checked" {/if}/>&nbsp;&nbsp;<label for="statCountDecline">{translate key="manager.statistics.statistics.count.decline"}</label></td>
		<td colspan="2" class="value">{translate key="manager.statistics.statistics.count.value" count=$limitedArticleStatistics.submissionsDecline percentage=$limitedArticleStatistics.submissionsDeclinePercent}</td>
			</tr>
			<tr valign="top">
				<td width="20%" class="label"><input type="checkbox" id="statCountRevise" name="statCountRevise" {if $statCountRevise}checked="checked" {/if}/>&nbsp;&nbsp;<label for="statCountRevise">{translate key="manager.statistics.statistics.count.revise"}</label></td>
				<td width="80%" colspan="2" class="value">{translate key="manager.statistics.statistics.count.value" count=$limitedArticleStatistics.submissionsRevise percentage=$limitedArticleStatistics.submissionsRevisePercent}</td>
			</tr>
			<tr valign="top">
				<td width="20%" class="label"><input type="checkbox" id="statDaysPerReview" name="statDaysPerReview" {if $statDaysPerReview}checked="checked" {/if}/>&nbsp;&nbsp;<label for="statDaysPerReview">{translate key="manager.statistics.statistics.daysPerReview"}</label></td>
				<td colspan="2" class="value">
					{assign var=daysPerReview value=$reviewerStatistics.daysPerReview}
					{math equation="round($daysPerReview)"}
				</td>
			</tr>
	<tr>
		<td class="label"><input type="checkbox" id="statDaysToPublication" name="statDaysToPublication" {if $statDaysToPublication}checked="checked" {/if}/>&nbsp;&nbsp;<label for="statDaysToPublication">{translate key="manager.statistics.statistics.daysToPublication"}</label></td>
				<td colspan="2" class="value">{$limitedArticleStatistics.daysToPublication}</td>
			</tr>
	<tr>
		<td class="label"><input type="checkbox" id="statRegisteredUsers" name="statRegisteredUsers" {if $statRegisteredUsers}checked="checked" {/if}/><label for="statRegisteredUsers">{translate key="manager.statistics.statistics.registeredUsers"}</label></td>
				<td colspan="2" class="value">{translate key="manager.statistics.statistics.totalNewValue" numTotal=$allUserStatistics.totalUsersCount numNew=$userStatistics.totalUsersCount}</td>
			</tr>
	<tr>
		<td class="label"><input type="checkbox" id="statRegisteredReaders" name="statRegisteredReaders" {if $statRegisteredReaders}checked="checked" {/if}/><label for="statRegisteredReaders">{translate key="manager.statistics.statistics.registeredReaders"}</label></td>
				<td colspan="2" class="value">{translate key="manager.statistics.statistics.totalNewValue" numTotal=$allUserStatistics.reader|default:"0" numNew=$userStatistics.reader|default:"0"}</td>
			</tr>
		
			{if $currentJournal->getSetting('publishingMode') == $smarty.const.PUBLISHING_MODE_SUBSCRIPTION}
		<tr>
					<td colspan="3" class="label"><input type="checkbox" id="statSubscriptions" name="statSubscriptions" {if $statSubscriptions}checked="checked" {/if}/><label for="statSubscriptions">{translate key="manager.statistics.statistics.subscriptions"}</label></td>
				</tr>
				{foreach from=$allSubscriptionStatistics key=type_id item=stats}
		<tr>
			<td class="label">&nbsp;&nbsp;{$stats.name}:</td>
					<td colspan="2" class="value">{translate key="manager.statistics.statistics.totalNewValue" numTotal=$stats.count|default:"0" numNew=$subscriptionStatistics.$type_id.count|default:"0"}</td>
				</tr>
				{/foreach}
			{/if}
		
	<tr>
		<td colspan="3" class="label"><input type="checkbox" id="statViews" name="statViews" {if $statViews}checked="checked" {/if}/><label for="statViews">{translate key="manager.statistics.statistics.articleViews"}</label></td>
			</tr>
		</table>
		<p>{translate key="manager.statistics.statistics.note"}</p>
		
		{translate key="manager.statistics.statistics.makePublic"}<br/>
		<input type="submit" class="button defaultButton" value="{translate key="common.record"}"/>
	</form>
</div>

