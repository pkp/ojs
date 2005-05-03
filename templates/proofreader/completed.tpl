{**
 * completed.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show proofreader's submission archive.
 *
 * $Id$
 *}

<table width="100%" class="listing">
	<tr><td colspan="7" class="headseparator">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="common.assign"}</td>
		<td width="5%">{translate key="submissions.sec"}</td>
		<td width="25%">{translate key="article.authors"}</td>
		<td width="30%">{translate key="article.title"}</td>
		<td width="5%">{translate key="submission.complete"}</td>
		<td width="25%" align="right">{translate key="common.status"}</td>
	</tr>
	<tr><td colspan="7" class="headseparator">&nbsp;</td></tr>
{iterate from=submissions item=submission}
	{assign var="articleId" value=$submission->getArticleId()}
	{assign var="proofAssignment" value=$submission->getProofAssignment()}

	<tr valign="top">
		<td>{$articleId}</td>
		<td>{$proofAssignment->getDateProofreaderNotified()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."}</td>
		<td><a href="{$requestPageUrl}/submission/{$articleId}" class="action">{$submission->getArticleTitle()|truncate:60:"..."}</a></td>
		<td>{$proofAssignment->getDateProofreaderCompleted()|date_format:$dateFormatTrunc}</td>
		<td align="right">
			{assign var="status" value=$submission->getStatus()}
			{if $status == STATUS_ARCHIVED}
				{translate key="submissions.archived"}
			{elseif $status == STATUS_QUEUED}
				{translate key="submissions.queued"}
			{elseif $status == STATUS_SCHEDULED}
				{translate key="submissions.scheduled"}
			{elseif $status == STATUS_PUBLISHED}
				{print_issue_id articleId="$articleId"}			
			{elseif $status == STATUS_DECLINED}
				{translate key="submissions.declined"}								
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
	</table>
	{page_links name="submissions" page=$submissions->getPage() pageCount=$submissions->getPageCount()}
	<br /><br />
{/if}
