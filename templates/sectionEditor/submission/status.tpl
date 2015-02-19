{**
 * templates/sectionEditor/submission/status.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the submission status table.
 *}
<div id="status">
<h3>{translate key="common.status"}</h3>

<table width="100%" class="data">
	<tr>
		{assign var="status" value=$submission->getSubmissionStatus()}
		<td width="20%" class="label">{translate key="common.status"}</td>
		<td width="30%" class="value">
			{if $status == STATUS_ARCHIVED}{translate key="submissions.archived"}
			{elseif $status==STATUS_QUEUED_UNASSIGNED}{translate key="submissions.queuedUnassigned"}
			{elseif $status==STATUS_QUEUED_EDITING}{translate key="submissions.queuedEditing"}
			{elseif $status==STATUS_QUEUED_REVIEW}{translate key="submissions.queuedReview"}
			{elseif $status==STATUS_PUBLISHED}{translate key="submissions.published"}&nbsp;&nbsp;&nbsp;&nbsp;{$issue->getIssueIdentification()|escape}
			{elseif $status==STATUS_DECLINED}{translate key="submissions.declined"}
			{/if}
		</td>
		<td width="50%" class="value">
			{if $status != STATUS_ARCHIVED}
				<a href="{url op="unsuitableSubmission" articleId=$submission->getId()}" class="action">{translate key="editor.article.archiveSubmission"}</a>
			{else}
				<a href="{url op="restoreToQueue" path=$submission->getId()}" class="action">{translate key="editor.article.restoreToQueue"}</a>
			{/if}
		</td>
	</tr>
	<tr>
		<td class="label">{translate key="submission.initiated"}</td>
		<td colspan="2" class="value">{$submission->getDateStatusModified()|date_format:$dateFormatShort}</td>
	</tr>
	<tr>
		<td class="label">{translate key="submission.lastModified"}</td>
		<td colspan="2" class="value">{$submission->getLastModified()|date_format:$dateFormatShort}</td>
	</tr>
{if $enableComments}
	<tr>
		<td class="label">{translate key="comments.readerComments"}</td>
		<td class="value">{translate key=$submission->getCommentsStatusString()}</td>
		<td class="value"><form action="{url op="updateCommentsStatus" path=$submission->getId()}" method="post">{translate key="submission.changeComments"} <select name="commentsStatus" size="1" class="selectMenu">{html_options_translate options=$commentsStatusOptions selected=$submission->getCommentsStatus()}</select> <input type="submit" value="{translate key="common.record"}" class="button" /></form></td>
	</tr>
{/if}
</table>
</div>

