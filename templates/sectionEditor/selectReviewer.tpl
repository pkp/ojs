{**
 * selectReviewer.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List reviewers and give the ability to select a reviewer.
 *
 * $Id$
 *}
{assign var="pageTitle" value="user.role.reviewers"}
{include file="common/header.tpl"}

<h3>{translate key="editor.article.selectReviewer"}</h3>
<form name="submit" method="post" action="{url op="selectReviewer" path=$articleId}">
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
	</select>
	<input type="text" size="10" name="search" class="textField" value="{$search|escape}" />&nbsp;<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<p>{foreach from=$alphaList item=letter}<a href="{url op="selectReviewer" path=$articleId searchInitial=$letter}">{if $letter == $searchInitial}<strong>{$letter}</strong>{else}{$letter}{/if}</a> {/foreach}<a href="{url op="selectReviewer" path=$articleId}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

<p><a class="action" href="{url op="enrollSearch" path=$articleId}">{translate key="sectionEditor.review.enrollReviewer"}</a>&nbsp;|&nbsp;<a class="action" href="{url op="createReviewer" path=$articleId}">{translate key="sectionEditor.review.createReviewer"}</a>{foreach from=$reviewerDatabaseLinks item="link"}{if !empty($link.title) && !empty($link.url)}&nbsp;|&nbsp;<a href="{$link.url|escape}" target="_new" class="action">{$link.title|escape}</a>{/if}{/foreach}</p>

<a name="reviewers"></a>

<table class="listing" width="100%">
{assign var=numCols value=7}
{if $rateReviewerOnQuality}
	{assign var=numCols value=$numCols+1}
{/if}
<tr><td colspan="{$numCols}" class="headseparator">&nbsp;</td></tr>
<tr class="heading" valign="bottom">
	<td width="20%">{translate key="user.name"}</td>
	<td>{translate key="user.interests"}</td>
	{if $rateReviewerOnQuality}
		<td width="7%">{translate key="reviewer.averageQuality"}</td>
	{/if}
	<td width="7%">{translate key="reviewer.completedReviews"}</td>
	<td width="7%">{translate key="editor.submissions.averageTime"}</td>
	<td width="13%">{translate key="editor.submissions.lastAssigned"}</td>
	<td width="5%">{translate key="common.active"}</td>
	<td width="7%" class="heading">{translate key="common.action"}</td>
</tr>
<tr><td colspan="{$numCols}" class="headseparator">&nbsp;</td></tr>
{iterate from=reviewers item=reviewer}
{assign var="userId" value=$reviewer->getUserId()}
{assign var="qualityCount" value=$averageQualityRatings[$userId].count}
{assign var="reviewerStats" value=$reviewerStatistics[$userId]}

<tr valign="top">
	<td><a class="action" href="{url op="userProfile" path=$userId}">{$reviewer->getFullName()|escape}</a></td>
	<td>{$reviewer->getUserInterests()|escape}</td>
	{if $rateReviewerOnQuality}<td>
		{if $qualityCount}{$averageQualityRatings[$userId].average|string_format:"%.1f"}
		{else}{translate key="common.notApplicableShort"}{/if}
	</td>{/if}

	<td>
		{if $completedReviewCounts[$userId]}
			{$completedReviewCounts[$userId]}
		{else}
			0
		{/if}
	</td>

	<td>
		{if $reviewerStats.average_span}
			{math equation="round(theSpan)" theSpan=$reviewerStats.average_span}
		{else}
			&mdash;
		{/if}
	</td>
	<td>{if $reviewerStats.last_notified}{$reviewerStats.last_notified|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
	<td>{$reviewerStats.incomplete|default:0}</td>
	<td>
		{if $reviewer->review_id}
			{translate key="common.alreadyAssigned"}
		{else}
		<a class="action" href="{url op="selectReviewer" path=$articleId|to_array:$reviewer->getUserId()}">{translate key="common.assign"}</a>
		{/if}
	</td>
</tr>
<tr><td colspan="{$numCols}" class="{if $reviewers->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $reviewers->wasEmpty()}
<tr>
<td colspan="{$numCols}" class="nodata">{translate key="manager.people.noneEnrolled"}</td>
</tr>
<tr><td colspan="{$numCols}" class="endseparator">&nbsp;</td></tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$reviewers}</td>
		<td colspan="{$numCols-2}" align="right">{page_links anchor="reviewers" name="reviewers" iterator=$reviewers searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth}</td>
	</tr>
{/if}
</table>

<h4>{translate key="common.notes"}</h4>
<p>{translate key="editor.article.selectReviewerNotes"}</p>

{include file="common/footer.tpl"}
