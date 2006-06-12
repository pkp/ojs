{**
 * status.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the submission status table.
 *
 * $Id$
 *}

<script type="text/javascript">
{literal}
<!--
function confirmNotifyUnsuitable() {
	$result = confirm("{/literal}{translate|escape:"quote" key="editor.article.emailAuthorOnArchive"}{literal}");
	if ($result) {
		document.location = "{/literal}{url op="unsuitableSubmission" articleId=$submission->getArticleId() escape=false}{literal}"
	} else {
		document.location = "{/literal}{url op="archiveSubmission" path=$submission->getArticleId() escape=false}{literal}";
	}
	return false;
}
// -->
{/literal}
</script>

<a name="status"></a>
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
			{elseif $status==STATUS_PUBLISHED}{translate key="submissions.published"}&nbsp;&nbsp;&nbsp;&nbsp;{$issue->getIssueIdentification()}
			{elseif $status==STATUS_DECLINED}{translate key="submissions.declined"}
			{/if}
		</td>
		<td width="50%" class="value">
			{if $status != STATUS_ARCHIVED}
				<a onclick="confirmNotifyUnsuitable()" href="#" class="action">{translate key="editor.article.archiveSubmission"}</a>
			{else}
				<a href="{url op="restoreToQueue" path=$submission->getArticleId()}" class="action">{translate key="editor.article.restoreToQueue"}</a>
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
