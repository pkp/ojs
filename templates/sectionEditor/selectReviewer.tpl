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

{assign var="pageTitle" value="user.role.reviewers"}
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
{if $rateReviewerOnQuality}
	{assign var=numCols value=$numCols+2}
{/if}
<tr><td colspan="{$numCols}" class="headseparator"></td></tr>
<tr class="heading" valign="bottom">
	<td width="15%">{translate key="user.name"}</td>
	<td>{translate key="user.interests"}</td>
	{if $rateReviewerOnQuality}
		<td width="7%">{translate key="reviewer.averageQuality"}</td>
		<td width="7%">{translate key="reviewer.numberOfRatings"}</td>
	{/if}
	<td width="15%">{translate key="editor.submissions.lastAssigned"}</td>
	<td width="7%">{translate key="editor.submissions.averageTime"}</td>
	<td width="7%" class="heading">{translate key="common.action"}</td>
</tr>
<tr><td colspan="{$numCols}" class="headseparator"></td></tr>
{foreach from=$reviewers name="users" item=reviewer}
{assign var="userId" value=$reviewer->getUserId()}
{assign var="qualityCount" value=$averageQualityRatings[$userId].count}
{assign var="reviewerStats" value=$reviewerStatistics[$userId]}

<tr valign="top">
	<td><a class="action" href="{$requestPageUrl}/userProfile/{$userId}">{$reviewer->getFullName()}</a></td>
	<td>{$reviewer->getInterests()}</td>
	{if $rateReviewerOnQuality}<td>
		{if $qualityCount}{$averageQualityRatings[$userId].average|string_format:"%.1f"}
		{else}{translate key="common.notApplicableShort"}{/if}
	</td>{/if}

	{if $rateReviewerOnQuality}
		<td>
			{if $averageQualityRatings[$userId].count}
				{$averageQualityRatings[$userId].count}
			{else}
				0
			{/if}
		</td>
	{/if}

	<td>{if $reviewerStats.last_notified}{$reviewerStats.last_notified|date_format:$dateFormatShort}{if $reviewerStats.incomplete}+{/if}{else}&mdash;{/if}</td>
	<td>
		{if $reviewerStats.average_span}
			{$reviewerStats.average_span}
		{else}
			&mdash;
		{/if}
	</td>
	<td>
		{if $reviewer->review_id and !$reviewer->cancelled}
			{translate key="common.alreadyAssigned"}
		{else}
		<a class="action" href="{$requestPageUrl}/selectReviewer/{$articleId}/{$reviewer->getUserId()}" class="tableAction">{translate key="common.assign"}</a>
		{/if}
	</td>
</tr>
<tr><td colspan="{$numCols}" class="{if $smarty.foreach.users.last}end{/if}separator"></tr>
{foreachelse}
<tr>
<td colspan="{$numCols}" class="nodata">{translate key="manager.people.noneEnrolled"}</td>
</tr>
<tr><td colspan="{$numCols}" class="endseparator"></tr>
{/foreach}
</table>
<p>
	<h4>{translate key="common.notes"}</h4>
	{translate key="editor.article.selectReviewerNotes"}
</p>

{include file="common/footer.tpl"}
