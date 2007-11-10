{**
 * active.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the details of active submissions.
 *
 * $Id$
 *}
<a name="submissions"></a>

<table class="listing" width="100%">
	<tr><td colspan="6" class="headseparator">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="submissions.submit"}</td>
		<td width="5%">{translate key="submissions.sec"}</td>
		<td width="25%">{translate key="article.authors"}</td>
		<td width="35%">{translate key="article.title"}</td>
		<td width="25%" align="right">{translate key="common.status"}</td>
	</tr>
	<tr><td colspan="6" class="headseparator">&nbsp;</td></tr>

{iterate from=submissions item=submission}
	{assign var="articleId" value=$submission->getArticleId()}
	{assign var="progress" value=$submission->getSubmissionProgress()}

	<tr valign="top">
		<td>{$articleId}</td>
		<td>{if $submission->getDateSubmitted()}{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
		<td>{$submission->getSectionAbbrev()|escape}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."|escape}</td>
		{if $progress == 0}
			<td><a href="{url op="submission" path=$articleId}" class="action">{if $submission->getArticleTitle()}{$submission->getArticleTitle()|strip_unsafe_html|truncate:60:"..."}{else}{translate key="common.untitled"}{/if}</a></td>
			<td align="right">
				{assign var="status" value=$submission->getSubmissionStatus()}
				{if $status==STATUS_ARCHIVED}{translate key="submissions.archived"}
				{elseif $status==STATUS_QUEUED_UNASSIGNED}{translate key="submissions.queuedUnassigned"}
				{elseif $status==STATUS_QUEUED_EDITING}<a href="{url op="submissionEditing" path=$articleId}" class="action">{translate key="submissions.queuedEditing"}</a>
				{elseif $status==STATUS_QUEUED_REVIEW}<a href="{url op="submissionReview" path=$articleId}" class="action">{translate key="submissions.queuedReview"}</a>
				{elseif $status==STATUS_PUBLISHED}{translate key="submissions.published"}
				{elseif $status==STATUS_DECLINED}{translate key="submissions.declined"}
				{/if}

				{** Payment related actions *}
				{if $status==STATUS_QUEUED_UNASSIGNED || $status==STATUS_QUEUED_REVIEW}
					{if $submissionEnabled && !$completedPaymentDAO->hasPaidSubmission($submission->getJournalId(), $submission->getArticleId())}
						<br />
						<a href="{url op="paySubmissionFee" path="$articleId"}" class="action">{translate key="payment.submission.paySubmission"}</a>					
					{elseif $fastTrackEnabled}
						<br />
						{if $submission->getFastTracked()}
							{translate key="payment.fastTrack.inFastTrack"}
						{else}
							<a href="{url op="payFastTrackFee" path="$articleId"}" class="action">{translate key="payment.fastTrack.payFastTrack"}</a>
						{/if}
					{/if}
				{elseif $status==STATUS_QUEUED_EDITING}
					{if $publicationEnabled}
						<br />
						{if $completedPaymentDAO->hasPaidPublication($submission->getJournalId(), $submission->getArticleId())}
							{translate key="payment.publication.publicationPaid}
						{else}
						 	<a href="{url op="payPublicationFee" path="$articleId"}" class="action">{translate key="payment.publication.payPublication"}</a>
						 {/if}
				{/if}		
		{/if}
			</td>
		{else}
			<td><a href="{url op="submit" path=$progress articleId=$articleId}" class="action">{if $submission->getArticleTitle()}{$submission->getArticleTitle()|strip_unsafe_html|truncate:60:"..."}{else}{translate key="common.untitled"}{/if}</a></td>
			<td align="right">{translate key="submissions.incomplete"}<br /><a href="{url op="deleteSubmission" path=$articleId}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="author.submissions.confirmDelete"}')">{translate key="common.delete"}</a></td>
		{/if}

	</tr>

	<tr>
		<td colspan="6" class="{if $submissions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $submissions->wasEmpty()}
	<tr>
		<td colspan="6" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="6" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="4" align="left">{page_info iterator=$submissions}</td>
		<td colspan="2" align="right">{page_links anchor="submissions" name="submissions" iterator=$submissions}</td>
	</tr>
{/if}
</table>
