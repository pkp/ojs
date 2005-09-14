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

{assign_translate var="pageTitleTranslated" key="submission.page.review" id=$submission->getArticleId()}
{assign var="pageCrumbTitle" value="submission.review"}

{include file="common/header.tpl"}

<script type="text/javascript">
{literal}
<!--
function confirmSubmissionCheck() {
	if (document.recommendation.recommendation.value=='') {
		alert('{/literal}{translate|escape:"javascript" key="reviewer.article.mustSelectDecision"}{literal}');
		return false;
	}
	return confirm('{/literal}{translate|escape:"javascript" key="reviewer.article.confirmDecision"}{literal}');
}
// -->
{/literal}
</script>

<h3>{translate key="reviewer.article.submissionToBeReviewed"}</h3>

<table width="100%" class="data">
<tr valign="top">
	<td width="20%" class="label">{translate key="article.title"}</td>
	<td width="80%" class="value">{$submission->getArticleTitle()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="article.journalSection"}</td>
	<td class="value">{$submission->getSectionTitle()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="article.abstract"}</td>
	<td class="value">{$submission->getArticleAbstract()|strip_unsafe_html|nl2br}</td>
</tr>
{if $editor}
	<tr valign="top">
		<td class="label">{translate key="reviewer.article.submissionEditor"}</td>
		<td class="value">
			{assign var=emailString value="`$editor->getEditorFullName()` <`$editor->getEditorEmail()`>"}
			{assign var=emailStringEscaped value=$emailString|escape:"url"}
			{assign var=urlEscaped value=$currentUrl|escape:"url"}
			{assign var=subjectEscaped value=$submission->getArticleTitle()|escape:"url"}
			{$editor->getEditorFullName()|escape} {icon name="mail" url="`$pageUrl`/user/email?to[]=$emailStringEscaped&amp;redirectUrl=$urlEscaped&amp;subject=$subjectEscaped"}
		</td>
	</tr>
{/if}
</table>

<div class="separator"></div>

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

<div class="separator"></div>

<h3>{translate key="reviewer.article.reviewSteps"}</h3>

{include file="common/formErrors.tpl"}

<table width="100%" class="data">
<tr valign="top">
	<td width="3%">1.</td>
	<td width="97%"><span class="instruct">{translate key="reviewer.article.reviewerInstruction1a"}{if $editor}, {$editor->getEditorFullName()},{/if} {translate key="reviewer.article.reviewerInstruction1b"}</span></td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td>
		{translate key="submission.response"}&nbsp;&nbsp;&nbsp;&nbsp;
		{if not $confirmedStatus}
			{if !$submission->getCancelled()}
				{translate key="reviewer.article.canDoReview"} {icon name="mail" url="`$requestPageUrl`/confirmReview?reviewId=`$submission->getReviewId()`"}
				&nbsp;&nbsp;&nbsp;&nbsp;
				{translate key="reviewer.article.cannotDoReview"} {icon name="mail" url="`$requestPageUrl`/confirmReview?reviewId=`$submission->getReviewId()`&amp;declineReview=1"}
			{else}
				{translate key="reviewer.article.canDoReview"} {icon name="mail" disabled="disabled" url="`$requestPageUrl`/confirmReview?reviewId=`$submission->getReviewId()`"}
				&nbsp;&nbsp;&nbsp;&nbsp;
				{translate key="reviewer.article.cannotDoReview"} {icon name="mail" disabled="disabled" url="`$requestPageUrl`/confirmReview?reviewId=`$submission->getReviewId()`&amp;declineReview=1"}
			{/if}
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
	<td><span class="instruct">{translate key="reviewer.article.reviewerInstruction2"}</span></td>
</tr>
<tr>
	<td colspan="2">&nbsp;</td>
</tr>
{else}
{assign var="haveGuide" value=false}
{/if}
<tr valign="top">
	<td>{if $haveGuide}3{else}2{/if}.</td>
	<td><span class="instruct">{translate key="reviewer.article.reviewerInstruction3"}</span></td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td>
		<table width="100%" class="data">
			{if ($confirmedStatus and not $declined) or not $journal->getSetting('restrictReviewerFileAccess')}
			<tr valign="top">
				<td width="30%" class="label">
					{translate key="submission.submissionManuscript"}
				</td>
				<td class="value" width="70%">
					{if $reviewFile}
					{if $submission->getDateConfirmed() or not $journal->getSetting('restrictReviewerAccessToFile')}
						<a href="{$requestPageUrl}/downloadFile/{$submission->getReviewId()}/{$submission->getArticleId()}/{$reviewFile->getFileId()}/{$reviewFile->getRevision()}" class="file">{$reviewFile->getFileName()|escape}</a>
					{else}{$reviewFile->getFileName()|escape}{/if}
					&nbsp;&nbsp;{$reviewFile->getDateModified()|date_format:$dateFormatShort}
					{else}
					{translate key="common.none"}
					{/if}
				</td>
			</tr>
			<tr valign="top">
				<td class="label">
					{translate key="article.suppFiles"}
				</td>
				<td class="value">
					{assign var=sawSuppFile value=0}
					{foreach from=$suppFiles item=suppFile}
						{if $suppFile->getShowReviewers() }
							{assign var=sawSuppFile value=1}
							<a href="{$requestPageUrl}/downloadFile/{$submission->getReviewId()}/{$submission->getArticleId()}/{$suppFile->getFileId()}" class="file">{$suppFile->getFileName()|escape}</a><br />
						{/if}
					{/foreach}

					{if !$sawSuppFile}
						{translate key="common.none"}
					{/if}
				</td>
			</tr>
			{else}
			<tr><td class="nodata">{translate key="reviewer.article.restrictedFileAccess"}</td></tr>
			{/if}
		</table>
	</td>
</tr>
<tr>
	<td colspan="2">&nbsp;</td>
</tr>
<tr valign="top">
	<td>{if $haveGuide}4{else}3{/if}.</td>
	<td><span class="instruct">{translate key="reviewer.article.reviewerInstruction4a"}</span></td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td>
		{translate key="submission.logType.review"} 
		{if $confirmedStatus and not $declined}
			<a href="javascript:openComments('{$requestPageUrl}/viewPeerReviewComments/{$submission->getArticleId()}/{$submission->getReviewId()}');" class="icon">{icon name="comment"}</a>
		{else}
			 {icon name="comment" disabled="disabled"}
		{/if}
	</td>
</tr>
<tr>
	<td colspan="2">&nbsp;</td>
</tr>
<tr valign="top">
	<td>{if $haveGuide}5{else}4{/if}.</td>
	<td><span class="instruct">{translate key="reviewer.article.reviewerInstruction5"}</span></td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td>
		<table class="data" width="100%">
			{foreach from=$submission->getReviewerFileRevisions() item=reviewerFile key=key}
				{assign var=uploadedFileExists value="1"}
				<tr valign="top">
				<td class="label" width="30%">
					{if $key eq "0"}
						{translate key="reviewer.article.uploadedFile"}
					{/if}
				</td>
				<td class="value" width="70%">
					<a href="{$requestPageUrl}/downloadFile/{$submission->getReviewId()}/{$submission->getArticleId()}/{$reviewerFile->getFileId()}/{$reviewerFile->getRevision()}" class="file">{$reviewerFile->getFileName()|escape}</a>
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
			</span>
		{/if}
	</td>
</tr>
<tr>
	<td colspan="2">&nbsp;</td>
</tr>
<tr valign="top">
	<td>{if $haveGuide}6{else}5{/if}.</td>
	<td><span class="instruct">{translate key="reviewer.article.reviewerInstruction6"}</span></td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td>
		<table class="data" width="100%">
			<tr valign="top">
				<td class="label" width="30%">{translate key="submission.recommendation"}</td>
				<td class="value" width="70%">
				{if $submission->getRecommendation()}
					{assign var="recommendation" value=$submission->getRecommendation()}
					<b>{translate key=$reviewerRecommendationOptions.$recommendation}</b>&nbsp;&nbsp;
					{$submission->getDateCompleted()|date_format:$dateFormatShort}
				{else}
					<form name="recommendation" method="post" action="{$requestPageUrl}/recordRecommendation">
					<input type="hidden" name="reviewId" value="{$submission->getReviewId()}" />
					<select name="recommendation" {if not $confirmedStatus or $declined or $submission->getCancelled() or (!$reviewAssignment->getMostRecentPeerReviewComment() and !$uploadedFileExists)}disabled="disabled"{/if} class="selectMenu">
						{html_options_translate options=$reviewerRecommendationOptions selected=''}
					</select>&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="submit" name="submit" onclick="return confirmSubmissionCheck()" class="button" value="{translate key="reviewer.article.submitReview"}" {if not $confirmedStatus or $declined or $submission->getCancelled() or (!$reviewAssignment->getMostRecentPeerReviewComment() and !$uploadedFileExists)}disabled="disabled"{/if} />
					</form>					
				{/if}
				</td>		
			</tr>
		</table>
	</td>
</tr>
</table>

{if $haveGuide}
<div class="separator"></div>
<h3>{translate key="reviewer.article.reviewerGuidelines"}</h3>
<p>{$journal->getSetting('reviewGuidelines')|nl2br}</p>
{/if}

{include file="common/footer.tpl"}

