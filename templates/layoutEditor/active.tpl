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
	<tr><td colspan="6" class="headseparator">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="common.assign"}</td>
		<td width="5%">{translate key="submissions.sec"}</td>
		<td width="30%">{translate key="article.authors"}</td>
		<td width="40%">{translate key="article.title"}</td>
		<td width="15%" align="right">{translate key="common.status"}</td>
	</tr>
	<tr><td colspan="6" class="headseparator">&nbsp;</td></tr>

{iterate from=submissions item=submission}
	{assign var="articleId" value=$submission->getArticleId()}
	{assign var="layoutAssignment" value=$submission->getLayoutAssignment()}

	<tr valign="top">
		<td>{$articleId}</td>
		<td>{$layoutAssignment->getDateNotified()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()|escape}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."|escape}</td>
		<td><a href="{url op="submission" path=$articleId}" class="action">{$submission->getArticleTitle()|strip_unsafe_html|truncate:60:"..."}</a></td>
		<td align="right">
			{if not $layoutAssignment->getDateCompleted()}
				{translate key="submissions.initial"}
			{else}
				{translate key="submissions.proofread"}
			{/if}
		</td>
	</tr>
	<tr>
                <td colspan="7" class="{if $submissions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $submissions->wasEmpty()}
	<tr>
		<td colspan="7" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="7" class="endseparator">&nbsp;</td>
	</tr>
	</table>
{else}
	<tr>
		<td colspan="4" align="left">{page_info iterator=$submissions}</td>
		<td colspan="2" align="right">{page_links name="submissions" iterator=$submissions}</td>
	</tr>
{/if}
</table>
