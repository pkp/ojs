{**
 * active.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show layout editor's active submissions.
 *
 * $Id$
 *}

<table class="listing" width="100%">
	<tr><td colspan="6" class="headseparator"></td></tr>
	<tr class="heading">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="submissions.assigned"}</td>
		<td width="5%">{translate key="submissions.sec"}</td>
		<td width="30%">{translate key="article.authors"}</td>
		<td width="45%">{translate key="article.title"}</td>
		<td width="10%">{translate key="common.status"}</td>
	</tr>
	<tr><td colspan="6" class="headseparator"></td></tr>

{foreach name=submissions from=$submissions item=submission}
	{assign var="articleId" value=$submission->getArticleId()}
	{assign var="layoutAssignment" value=$submission->getLayoutAssignment()}

	<tr valign="top">
		<td>{$articleId}</td>
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
		<td colspan="7" class="endseparator"></td>
	</tr>
{/foreach}
</table>
