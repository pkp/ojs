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

<table class="listing" width="100%">
	<tr><td colspan="6" class="headseparator"></td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="submissions.submit"}</td>
		<td width="5%">{translate key="submissions.sec"}</td>
		<td width="25%">{translate key="article.authors"}</td>
		<td width="35%">{translate key="article.title"}</td>
		<td width="25%" align="right">{translate key="common.status"}</td>
	</tr>
	<tr><td colspan="6" class="headseparator"></td></tr>

{foreach name=submissions from=$submissions item=submission}
	{assign var="articleId" value=$submission->getArticleId()}
	{assign var="progress" value=$submission->getSubmissionProgress()}

	<tr valign="top">
		<td>{$articleId}</td>
		<td>{if $submission->getDateSubmitted()}{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
		<td>{$submission->getSectionAbbrev()}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."}</td>
		{if $progress == 0}
			<td><a href="{$requestPageUrl}/submission/{$articleId}" class="action">{if $submission->getArticleTitle()}{$submission->getArticleTitle()|truncate:60:"..."}{else}{translate key="common.untitled"}{/if}</a></td>
			<td align="right">
				{assign var="status" value=$submission->getSubmissionStatus()}
				{if $status==ARCHIVED}{translate key="submissions.archived"}
				{elseif $status==QUEUED_UNASSIGNED}{translate key="submissions.queuedUnassigned"}
				{elseif $status==QUEUED_EDITING}{translate key="submissions.queuedEditing"}
				{elseif $status==QUEUED_REVIEW}{translate key="submissions.queuedReview"}
				{elseif $status==SCHEDULED}{translate key="submissions.scheduled"}
				{elseif $status==PUBLISHED}{translate key="submissions.published"}
				{elseif $status==DECLINED}{translate key="submissions.declined"}
				{/if}
			</td>
		{else}
			<td><a href="{$pageUrl}/author/submit/{$progress}?articleId={$articleId}" class="action">{if $submission->getArticleTitle()}{$submission->getArticleTitle()|truncate:60:"..."}{else}{translate key="common.untitled"}{/if}</a></td>
			<td align="right">{translate key="submissions.incomplete"}<br /><a href="{$pageUrl}/author/deleteSubmission/{$articleId}" onclick="return confirm('{translate|escape:"javascript" key="author.submissions.confirmDelete"}')">{translate key="common.delete"}</a></td>
		{/if}

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

