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
	<tr><td colspan="6" class="headseparator"></td></tr>
	<tr class="heading">
		<td width="5%">{translate key="common.id"}</td>
		<td width="12%">{translate key="submissions.assigned"}</td>
		<td width="6%">{translate key="submissions.sec"}</td>
		<td>{translate key="article.authors"}</td>
		<td width="35%">{translate key="article.title"}</td>
		<td width="12%">{translate key="common.status"}</td>
	</tr>
	<tr><td colspan="6" class="headseparator"></td></tr>

{foreach name=submissions from=$submissions item=submission}
	{assign var="articleId" value=$submission->getArticleId()}
	{assign var="layoutAssignment" value=$submission->getLayoutAssignment()}

	<tr valign="top">
		<td><a href="{$requestPageUrl}/submission/{$articleId}">{$articleId}</a></td>
		<td>{$layoutAssignment->getDateNotified()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."}</td>
		<td><a href="{$requestPageUrl}/submission/{$articleId}" class="action">{$submission->getArticleTitle()|truncate:60:"..."}</a></td>
		<td>
			{if not $layoutAssignment->getDateCompleted()}
				{translate key="submissions.initial"}
			{else}
				{translate key="submissions.proofread"}
			{/if}
		</td>
	</tr>
	<tr>
                <td colspan="7" class="{if $smarty.foreach.submissions.last}end{/if}separator"></td>
	</tr>

{foreachelse}
	<tr>
		<td colspan="7" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="7" class="bottomseparator"></td>
	</tr>
{/foreach}
</table>
