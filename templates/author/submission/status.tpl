{**
 * status.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the submission status table.
 *
 * $Id$
 *}
<a name="status"></a>
<h3>{translate key="common.status"}</h3>

<table width="100%" class="data">
	<tr>
		{assign var="status" value=$submission->getSubmissionStatus()}
		<td width="20%" class="label">{translate key="common.status"}</td>
		<td width="80%" class="value">
			{if $status == STATUS_ARCHIVED}{translate key="submissions.archived"}
			{elseif $status==STATUS_QUEUED_UNASSIGNED}{translate key="submissions.queuedUnassigned"}
			{elseif $status==STATUS_QUEUED_EDITING}{translate key="submissions.queuedEditing"}
			{elseif $status==STATUS_QUEUED_REVIEW}{translate key="submissions.queuedReview"}
			{elseif $status==STATUS_PUBLISHED}{translate key="submissions.published"}&nbsp;&nbsp;&nbsp;&nbsp;{$issue->getIssueIdentification()}
			{elseif $status==STATUS_DECLINED}{translate key="submissions.declined"}
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
</table>
