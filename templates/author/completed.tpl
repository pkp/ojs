{**
 * completed.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the details of completed submissions.
 *
 * $Id$
 *}

<table class="listing" width="100%">
	<tr><td class="headseparator" colspan="6"></td></tr>
	<tr valign="bottom" class="heading">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="submissions.submit"}</td>
		<td width="5%">{translate key="submissions.sec"}</td>
		<td width="25%">{translate key="article.authors"}</td>
		<td width="35%">{translate key="article.title"}</td>
		<td width="25%" align="right">{translate key="common.status"}</td>
	</tr>
	<tr><td class="headseparator" colspan="6"></td></tr>
{foreach name=submissions from=$submissions item=submission}
	{assign var="articleId" value=$submission->getArticleId()}
	<tr valign="top">
		<td>{$articleId}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."}</td>
		<td><a href="{$requestPageUrl}/submission/{$articleId}" class="action">{$submission->getArticleTitle()|truncate:60:"..."}</a></td>
		<td align="right">
			{assign var="status" value=$submission->getSubmissionStatus()}
			{if $status == ARCHIVED}{translate key="submissions.archived"}
			{elseif $status==QUEUED_UNASSIGNED}{translate key="submissions.queuedUnassigned"}
			{elseif $status==QUEUED_EDITING}{translate key="submissions.queuedEditing"}
			{elseif $status==QUEUED_REVIEW}{translate key="submissions.queuedReview"}
			{elseif $status==SCHEDULED}{translate key="submissions.scheduled"}
			{elseif $status==PUBLISHED}{print_issue_id articleId="$articleId"}
			{elseif $status==DECLINED}{translate key="submissions.declined"}
			{/if}
		</td>
	</tr>

	<tr>
		<td colspan="6" class="{if $smarty.foreach.submissions.last}end{/if}separator"></td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="6" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="6" class="endseparator"></td>
	</tr>
	{/foreach}

</table>

