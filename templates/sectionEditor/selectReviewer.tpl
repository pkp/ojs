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

<h3>{translate key="editor.article.selectReviewer"}</h3>

<table class="listing" width="100%">
<tr valign="top">
	<td width="15%" class="heading">{translate key="user.username"}</td>
	<td width="40%" class="heading">{translate key="user.name"}</td>
	{if $rateReviewerOnTimeliness}<td width="15%" class="heading">{translate key="reviewer.averageTimeliness"}</td>{/if}
	{if $rateReviewerOnQuality}<td width="15%" class="heading">{translate key="reviewer.averageQuality"}</td>{/if}
	{if $rateReviewerOnTimeliness or $rateReviewerOnQuality}<td width="15%" class="heading">{translate key="reviewer.numberOfRatings"}</td>{/if}
	<td class="heading">{translate key="common.action"}</td>
</tr>
{foreach from=$reviewers item=reviewer}
{assign var="userId" value=$reviewer->getUserId()}
<tr valign="top">
	<td><a class="action" href="{$requestPageUrl}/userProfile/{$userId}">{$reviewer->getUsername()}</a></td>
	<td>{$reviewer->getFullName()}</td>
	{if $rateReviewerOnTimeliness}<td>
		{if $averageTimelinessRatings[$userId].count}{$averageTimelinessRatings[$userId].average|string_format:"%.1f"} / 5
		{else}{translate key="reviewer.notRated"}{/if}
	</td>{/if}
	{if $rateReviewerOnQuality}<td>
		{if $averageQualityRatings[$userId].count}{$averageQualityRatings[$userId].average|string_format:"%.1f"} / 5
		{else}{translate key="reviewer.notRated"}{/if}
	</td>{/if}

	{if $rateReviewerOnTimeliness and $rateReviewerOnQuality}<td>
		{if $averageTimelinessRatings[$userId].count eq $averageQualityRatings[$userId].count}
			{$averageTimelinessRatings[$userId].count}
		{else}
			{$averageTimelinessRatings[$userId].count} / {$averageQualityRatings[$userId].count}
		{/if}
	</td>
	{elseif $rateReviewerOnTimeliness}<td>{$averageTimelinessRatings[$userId].count}</td>
	{elseif $rateReviewerOnQuality}<td>{$averageQualityRatings[$userId].count}</td>{/if}

	<td>
		{if $reviewer->review_id}
			{if $reviewer->cancelled}
				<a class="action" href="{$requestPageUrl}/reinitiateReview/{$articleId}/{$reviewer->review_id}" class="tableAction">{translate key="editor.article.reinitiate"}</a>
			{/if}
		{else}
		<a class="action" href="{$requestPageUrl}/selectReviewer/{$articleId}/{$reviewer->getUserId()}" class="tableAction">{translate key="common.assign"}</a>
		{/if}
	</td>
</tr>
{foreachelse}
<tr>
<td colspan="3" class="noResults">{translate key="manager.people.noneEnrolled"}</td>
</tr>
{/foreach}
</table>

{include file="common/footer.tpl"}
