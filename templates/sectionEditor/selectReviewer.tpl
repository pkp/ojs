{**
 * selectReviewer.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List reviewers and give the ability to select a reviewer.
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.reviewer"}
{include file="common/header.tpl"}

{assign var="start" value="A"|ord}

<h3>{translate key="editor.article.selectReviewer"}</h3>
<form name="submit" method="post" action="{$requestPageUrl}/selectReviewer/{$articleId}">
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains">{translate key="form.contains"}</option>
		<option value="is">{translate key="form.is"}</option>
	</select>
	<input type="text" size="10" name="search" class="textField" />&nbsp;<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<p>{section loop=26 name=letters}<a href="{$requestPageUrl}/selectReviewer/{$articleId}?search_initial={$smarty.section.letters.index+$start|chr}">{$smarty.section.letters.index+$start|chr}</a> {/section}</p>

<p><a class="action" href="{$requestPageUrl}/enrollSearch/{$articleId}">{translate key="sectionEditor.review.addReviewer"}</a></p>

<table class="listing" width="100%">
{assign var=numCols value=5}
{if $rateReviewerOnTimeliness or $rateReviewerOnQuality}
	{assign var=numCols value=$numCols+1}
	{if $rateReviewerOnTimeliness}{assign var=numCols value=$numCols+1}{/if}
	{if $rateReviewerOnQuality}{assign var=numCols value=$numCols+1}{/if}
{/if}
<tr><td colspan="{$numCols}" class="headseparator"></td></tr>
<tr class="heading" valign="bottom">
	<td width="15%">{translate key="user.name"}</td>
	<td>{translate key="user.interests"}</td>
	{if $rateReviewerOnTimeliness}<td width="10%">{translate key="reviewer.averageTimeliness"}</td>{/if}
	{if $rateReviewerOnQuality}<td width="10%">{translate key="reviewer.averageQuality"}</td>{/if}
	{if $rateReviewerOnTimeliness or $rateReviewerOnQuality}<td width="10%">{translate key="reviewer.numberOfRatings"}</td>{/if}
	<td width="8%">{translate key="editor.submissions.lastAssigned"}</td>
	<td width="8%" class="heading">{translate key="common.action"}</td>
</tr>
<tr><td colspan="{$numCols}" class="headseparator"></td></tr>
{foreach from=$reviewers name="users" item=reviewer}
{assign var="userId" value=$reviewer->getUserId()}
{assign var="timelinessCount" value=$averageTimelinessRatings[$userId].count}
{assign var="qualityCount" value=$averageQualityRatings[$userId].count}
{assign var="reviewerStats" value=$reviewerStatistics[$userId]}

<tr valign="top">
	<td><a class="action" href="{$requestPageUrl}/userProfile/{$userId}">{$reviewer->getFullName()}</a></td>
	<td>{$reviewer->getInterests()}</td>
	{if $rateReviewerOnTimeliness}<td>
		{if $timelinessCount}{$averageTimelinessRatings[$userId].average|string_format:"%.1f"} / 5
		{else}{translate key="common.notApplicableShort"}{/if}
	</td>{/if}
	{if $rateReviewerOnQuality}<td>
		{if $qualityCount}{$averageQualityRatings[$userId].average|string_format:"%.1f"} / 5
		{else}{translate key="common.notApplicableShort"}{/if}
	</td>{/if}

	{if $rateReviewerOnTimeliness and $rateReviewerOnQuality}<td>
		{if $timelinessCount eq $qualityCount}
			{if $timelinessCount}
				{$averageTimelinessRatings[$userId].count}
			{else}
				0
			{/if}
		{else}
			{if $timelinessCount}{$timelinessCount}{else}0{/if} / {if $qualityCount}{$qualityCount}{else}0{/if}
		{/if}
	</td>
	{elseif $rateReviewerOnTimeliness}<td>{$averageTimelinessRatings[$userId].count}</td>
	{elseif $rateReviewerOnQuality}<td>{$averageQualityRatings[$userId].count}</td>{/if}

	<td>{if $reviewerStats.last_completed}{$reviewerStats.last_completed|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
	<td>
		{if $reviewer->review_id}
			{if $reviewer->cancelled}
				<a class="action" href="{$requestPageUrl}/reinitiateReview/{$articleId}/{$reviewer->review_id}" class="tableAction">{translate key="common.assign"}</a>
			{else}
				{translate key="common.assign"}
			{/if}
		{else}
		<a class="action" href="{$requestPageUrl}/selectReviewer/{$articleId}/{$reviewer->getUserId()}" class="tableAction">{translate key="common.assign"}</a>
		{/if}
	</td>
</tr>
<tr><td colspan="{$numCols}" class="{if $smarty.foreach.users.last}end{/if}separator"></tr>
{foreachelse}
<tr>
<td colspan="{$numCols}" class="noResults">{translate key="manager.people.noneEnrolled"}</td>
</tr>
{/foreach}
</table>
<p>{translate key="editor.article.selectReviewerNotes"}</p>
<a href="{$requestPageUrl}/submissionReview/{$articleId}">{translate key="submission.submissionEditing"}</a>

{include file="common/footer.tpl"}
