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

<div class="subTitle">{translate key="editor.article.selectReviewer"}</div>

<table class="rightPadded">
<tr class="heading">
	<th>{translate key="user.username"}</th>
	<th>{translate key="user.name"}</th>
	{if $rateReviewerOnTimeliness}<th>{translate key="reviewer.averageTimeliness"}</th>{/if}
	{if $rateReviewerOnQuality}<th>{translate key="reviewer.averageQuality"}</th>{/if}
	<!-- {if $rateReviewerOnTimeliness or $rateReviewerOnQuality}<th>{translate key="reviewer.numberOfRatings"}</th>{/if} -->
	<th></th>
</tr>
{foreach from=$reviewers item=reviewer}
{assign var="userId" value=$reviewer->getUserId()}
<tr class="{cycle values="row,rowAlt"}">
	<td><a href="{$requestPageUrl}/selectReviewer/{$articleId}/{$userId}">{$reviewer->getUsername()}</a></td>
	<td style="white-space: nowrap">{$reviewer->getFullName()}</td>
	{if $rateReviewerOnTimeliness}<td>
		{if
$averageTimelinessRatings[$userId].count}{$averageTimelinessRatings[$userId].average|string_format:"%.1f"} / 5
		{else}{translate key="reviewer.notRated"}{/if}
	</td>{/if}
	{if $rateReviewerOnQuality}<td>
		{if $averageQualityRatings[$userId].count}{$averageQualityRatings[$userId].average|string_format:"%.1f"} / 5
		{else}{translate key="reviewer.notRated"}{/if}
	</td>{/if}
	<!--
	{if $rateReviewerOnTimeliness and $rateReviewerOnQuality}<td>
		{if $averageTimelinessRatings[$userId].count eq $averageQualityRatings[$userId].count}
			{$averageTimelinessRatings[$userId].count}
		{else}
			{$averageTimelinessRatings[$userId].count} / {$averageQualityRatings[$userId].count}
		{/if}
	</td>
	{elseif $rateReviewerOnTimeliness}<td>{$averageTimelinessRatings[$userId].count}</td>
	{elseif $rateReviewerOnQuality}<td>{$averageQualityRatings[$userId].count}</td>{/if}
	-->
	<td><a href="{$requestPageUrl}/selectReviewer/{$articleId}/{$reviewer->getUserId()}" class="tableAction">Assign</a></td>
</tr>
{foreachelse}
<tr>
<td colspan="3" class="noResults">{translate key="manager.people.noneEnrolled"}</td>
</tr>
{/foreach}
</table>

{include file="common/footer.tpl"}
