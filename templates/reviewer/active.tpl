{**
 * active.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the details of active submissions.
 *
 * $Id$
 *}

<table class="listing">
	<tr><td colspan="5" class="headseparator"></td></tr>
	<tr class="heading">
		<td width="5%">{translate key="common.id"}</td>
		<td width="12%">{translate key="submissions.assigned"}</td>
		<td width="6%">{translate key="submissions.sec"}</td>
		<td width="69%">{translate key="article.title"}</td>
		<td width="8%">{translate key="submissions.reviewRound"}</td>
	</tr>
	<tr><td colspan="5" class="headseparator"></td></tr>

{foreach name=submissions from=$submissions item=submission}
	{assign var="articleId" value=$submission->getArticleId()}
	{assign var="reviewId" value=$submission->getReviewId()}

	<tr valign="top">
		<td>{$articleId}</td>
		<td>{$submission->getDateNotified()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()}</td>
		<td><a href="{$requestPageUrl}/submission/{$reviewId}" class="action">{$submission->getArticleTitle()|truncate:60:"..."}</a></td>
		<td>{$submission->getRound()}</td>
	</tr>
	<tr>
		<td colspan="6" class="{if $smarty.foreach.submissions.last}end{/if}separator"></td>
	</tr>
{foreachelse}

<tr>
		<td colspan="6" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="6" class="bottomseparator"></td>
	</tr>

{/foreach}

</table>
