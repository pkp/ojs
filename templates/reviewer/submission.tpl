{**
 * submission.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the reviewer administration page.
 *
 * FIXME: At "Notify The Editor", fix the date.
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submission"}
{include file="common/header.tpl"}

<h3>{translate key="reviewer.article.submissionToBeReviewed"}</h3>

<table width="100%" class="data">
<tr valign="top">
	<td width="20%" class="label">{translate key="article.title"}</td>
	<td width="80%" class="value">{$submission->getArticleTitle()}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="article.journalSection"}</td>
	<td class="value">{$submission->getSectionTitle()}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="article.abstract"}</td>
	<td class="value">{$submission->getArticleAbstract()}</td>
</tr>
{if $editor}
	<tr valign="top">
		<td class="label">{translate key="reviewer.article.submissionEditor"}</td>
		<td class="value">
			{$editor->getEditorFullName()}
			{icon url="mailto:`$editor->getEditorEmail()`" name="mail"}
		</td>
	</tr>
{/if}
</table>

<h3>{translate key="reviewer.article.reviewSchedule"}</h3>
<table width="100%" class="data">
<tr valign="top">
	<td class="label" width="20%">{translate key="reviewer.article.schedule.request"}</td>
	<td class="value" width="80%">{if $submission->getDateNotified()}{$submission->getDateNotified()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="reviewer.article.schedule.response"}</td>
	<td class="value">{if $submission->getDateConfirmed()}{$submission->getDateConfirmed()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="reviewer.article.schedule.submitted"}</td>
	<td class="value">{if $submission->getDateCompleted()}{$submission->getDateCompleted()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="reviewer.article.schedule.due"}</td>
	<td class="value">{if $submission->getDateDue()}{$submission->getDateDue()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
</tr>
</table>

<h3>{translate key="reviewer.article.reviewSteps"}</h3>
<table width="100%" class="data">
<ol>
<tr valign="top">
	<td width="3%">1.</td>
	<td width="97%"><span class="instruct">{translate key="reviewer.article.reviewerInstruction1a"}{if $editor}, {$editor->getEditorFullName()}&nbsp;{icon url="mailto:`$editor->getEditorEmail()`" name="mail"},{/if} {translate key="reviewer.article.reviewerInstruction1b"}</span></td>
</tr>
<tr valign="top">
	<td></td>
	<td>
		{translate key="submission.response"}&nbsp;&nbsp;&nbsp;&nbsp;
		{if not $confirmedStatus}
			<form method="post" action="{$requestPageUrl}/confirmReview">
				<input type="hidden" name="reviewId" value="{$submission->getReviewId()}" />
				<input class="button" {if $submission->getCancelled()}disabled="disabled" {/if}type="submit" name="acceptReview" value="{translate key="reviewer.article.canDoReview"}" />&nbsp;&nbsp;&nbsp;&nbsp;
				<input class="button" {if $submission->getCancelled()}disabled="disabled" {/if}type="submit" name="declineReview" value="{translate key="reviewer.article.cannotDoReview"}" />
			</form>
		{else}
			{if not $declined}{translate key="submission.accepted"}{else}{translate key="submission.rejected"}{/if}
		{/if}
	</td>
</tr>
<tr>
	<td colspan="2">&nbsp;</td>
</tr>
{if $journal->getSetting('reviewGuidelines')}
{assign var="haveGuide" value=true}
<tr valign="top">
        <td>2.</td>
	<td><span class="instruct">{translate key="reviewer.article.reviewerInstruction2"}.</span></td>
</tr>
<tr>
	<td colspan="2">&nbsp;</td>
</tr>
{else}
{assign var="haveGuide" value=false}
{/if}
<tr valign="top">
	<td>{if $haveGuide}3{else}2{/if}.</td>
	<td><span class="instruct">{translate key="reviewer.article.reviewerInstruction3"}.</span></td>
</tr>
<tr valign="top"">
	<td></td>
	<td>
		<table width="100%" class="data">
			{if ($confirmedStatus and not $declined) or not $journal->getSetting('restrictReviewerFileAccess')}
			<tr valign="top">
				<td width="30%" class="label">
					{translate key="submission.submissionManuscript"}
				</td>
				<td class="value" width="70%">
					{if $submission->getDateConfirmed() or not $journal->getSetting('restrictReviewerAccessToFile')}
						<a href="{$requestPageUrl}/downloadFile/{$submission->getReviewId()}/{$submission->getArticleId()}/{$reviewFile->getFileId()}/{$reviewFile->getRevision()}" class="file">{$reviewFile->getFileName()}</a>
					{else}{$reviewFile->getFileName()}{/if}
					&nbsp;&nbsp;{$reviewFile->getDateModified()|date_format:$dateFormatShort}
				</td>
			</tr>
			<tr valign="top">
				<td class="label">
					<span class="boldText">{translate key="article.suppFiles"}</span>
				</td>
				<td class="value">
					{foreach from=$suppFiles item=suppFile}
						<a href="{$requestPageUrl}/downloadFile/{$submission->getReviewId()}/{$submission->getArticleId()}/{$suppFile->getFileId()}" class="file">{$suppFile->getTitle()}</a><br />
					{foreachelse}
						{translate key="common.none"}
					{/foreach}
				</td>
			</tr>
			{else}
			<tr><td class="nodata">{translate key="reviewer.article.restrictedFileAccess"}.</td></tr>
			{/if}
		</table>
	</td>
</tr>
<tr>
	<td colspan="2">&nbsp;</td>
</tr>
<tr valign="top">
	<td>{if $haveGuide}4{else}3{/if}.</td>
	<td><span class="instruct">{translate key="reviewer.article.reviewerInstruction4a"}.</span></td>
</tr>
<tr valign="top">
	<td></td>
	<td>
		{translate key="submission.logType.review"} 
		{if $confirmedStatus and not $declined}
			<a href="javascript:openComments('{$requestPageUrl}/viewPeerReviewComments/{$submission->getArticleId()}/{$submission->getReviewId()}');" class="icon">{icon name="comment"}</a>
		{else}
			 {icon name="comment_disabled"}
		{/if}
	</td>
</tr>
<tr>
	<td colspan="2">&nbsp;</td>
</tr>
<tr valign="top">
	<td>{if $haveGuide}5{else}4{/if}.</td>
	<td><span class="instruct">{translate key="reviewer.article.reviewerInstruction5"}.</span></td>
</tr>
<tr valign="top">
	<td></td>
	<td>
		<table class="data" width="100%">
			{foreach from=$submission->getReviewerFileRevisions() item=reviewerFile key=key}
				<tr valign="top">
				<td class="label" width="30%">
					{if $key eq "0"}
						{translate key="reviewer.article.uploadedFile"}
					{/if}
				</td>
				<td class="value" width="70%">
					<a href="{$requestPageUrl}/downloadFile/{$submission->getReviewId()}/{$submission->getArticleId()}/{$reviewerFile->getFileId()}/{$reviewerFile->getRevision()}" class="file">{$reviewerFile->getFileName()}</a>
					{$reviewerFile->getDateModified()|date_format:$dateFormatShort}
					{if (!$submission->getRecommendation()) && (!$submission->getCancelled())}
						<a class="action" href="{$requestPageUrl}/deleteReviewerVersion/{$submission->getReviewId()}/{$reviewerFile->getFileId()}/{$reviewerFile->getRevision()}">{translate key="common.delete"}</a>
					{/if}
				</td>
				</tr>
			{foreachelse}
				<tr valign="top">
				<td class="label" width="30%">
					{translate key="reviewer.article.uploadedFile"}
				</td>
				<td class="nodata">
					{translate key="common.none"}
				</td>
				</tr>
			{/foreach}
		</table>
		{if not $submission->getRecommendation()}
			<form method="post" action="{$requestPageUrl}/uploadReviewerVersion" enctype="multipart/form-data">
				<input type="hidden" name="reviewId" value="{$submission->getReviewId()}" />
				<input type="file" name="upload" {if not $confirmedStatus or $declined or $submission->getCancelled()}disabled="disabled"{/if} class="uploadField" />
				<input type="submit" name="submit" value="{translate key="common.upload"}" {if not $confirmedStatus or $declined or $submission->getCancelled()}disabled="disabled"{/if} class="button" />
			</form>
			<span class="instruct">
				{translate key="reviewer.article.noteOnUploads"}
				<a href="http://economics.ca/cje/en/pdfclean.php">
				{translate key="reviewer.article.anonymization"}</a>
			</span>
		{/if}
	</td>
</tr>
<tr>
	<td colspan="2">&nbsp;</td>
</tr>
<tr valign="top">
	<td>{if $haveGuide}6{else}5{/if}.</td>
	<td><span class="instruct">{translate key="reviewer.article.reviewerInstruction6"}.</span></td>
</tr>
<tr valign="top">
	<td></td>
	<td>
		<table class="data" width="100%">
			<tr valign="top">
				<td class="label" width="30%">{translate key="submission.recommendation"}</td>
				<td class="value" width="70%">
				{if $submission->getRecommendation()}
					{assign var="recommendation" value=$submission->getRecommendation()}
					{translate key=$reviewerRecommendationOptions.$recommendation}
				{else}
					<form method="post" action="{$requestPageUrl}/recordRecommendation">
					<input type="hidden" name="reviewId" value="{$submission->getReviewId()}" />
					<select name="recommendation" {if not $confirmedStatus or $declined or $submission->getCancelled()}disabled="disabled"{/if}>
						<option value="1">{translate key="reviewer.article.decision.accept"}</option>
						<option value="2">{translate key="reviewer.article.decision.pendingRevisions"}</option>
						<option value="3">{translate key="reviewer.article.decision.resubmitHere"}</option>
						<option value="4">{translate key="reviewer.article.decision.resubmitElsewhere"}</option>
						<option value="5">{translate key="reviewer.article.decision.decline"}</option>
						<option value="6">{translate key="reviewer.article.decision.seeComments"}</option>
					</select>&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="submit" name="submit" class="button" value="{translate key="reviewer.article.submitReview"}" {if not $confirmedStatus or $declined or $submission->getCancelled()}disabled="disabled"{/if} />
					</form>					
				{/if}
				</td>		
			</tr>
		</table>
	</td>
</tr>
</table>

{if $journal->getSetting('reviewGuidelines')}
<br />

<h4>{translate key="reviewer.article.reviewerGuidelines"}</h4>
<p><span class="instruct">{$journal->getSetting('reviewGuidelines')|nl2br}</span></p>
{/if}

{include file="common/footer.tpl"}



