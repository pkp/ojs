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

<!--
<div class="tableContainer">
<table width="100%">
<tr class="submissionRow">
	<td class="submissionBox">
		<div class="leftAligned">
			<div class="submissionTitle">{$submission->getArticleTitle()}</div>
		</div>
		<div class="submissionId">{$submission->getArticleId()}</div>
	</td>
</tr>
</table>
</div>

<br /> -->

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="reviewer.article.submissionToBeReviewed"}</td>
</tr>
<tr class="submissionRow">
	<td>
		<table class="plainFormat" width="100%">
			<tr>
				<td class="reviewLabel"><span class="boldText">{translate key="article.title"}</span></td>
				<td><span class="submissionTitle">{$submission->getArticleTitle()}</span></td>
			</tr>
			<tr>
				<td class="reviewLabel"><span class="boldText">{translate key="article.journalSection"}</span></td>
				<td>{$submission->getSectionTitle()}</td>
			</tr>
			<tr>
				<td class="reviewLabel" valign="top"><span class="boldText">{translate key="article.abstract"}</span></td>
				<td>{$submission->getArticleAbstract()}</td>
			</tr>
			{if $editor}
			<tr>
				<td class="reviewLabel"><span class="boldText">{translate key="reviewer.article.submissionEditor"}</span></td>
				<td><a href="mailto:{$editor->getEditorEmail()}">{$editor->getEditorFullName()}</a></td>
			</tr>
			{/if}
		
		</table>
	</td>
</tr>
</table>
</div>

<br />

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="reviewer.article.reviewSchedule"}</td>
</tr>
<tr class="submissionRow">
	<td class="submissionBox" style="text-align: center">
		<div class="spacedList">
			<span class="boldText">{translate key="reviewer.article.schedule.request"}</span><br />
			{if $submission->getDateNotified()}{$submission->getDateNotified()|date_format:$dateFormatShort}{else}-{/if}
		</div>
		<div class="spacedList">
			<span class="boldText">{translate key="reviewer.article.schedule.response"}</span><br />
			{if $submission->getDateConfirmed()}{$submission->getDateConfirmed()|date_format:$dateFormatShort}{else}-{/if}
		</div>
		<div class="spacedList">
			<span class="boldText">{translate key="reviewer.article.schedule.submitted"}</span><br />
			{if $submission->getDateCompleted()}{$submission->getDateCompleted()|date_format:$dateFormatShort}{else}-{/if}
		</div>
		<div class="spacedList">
			<span class="boldText">{translate key="reviewer.article.schedule.due"}</span><br />
			{if $submission->getDateDue()}{$submission->getDateDue()|date_format:$dateFormatShort}{else}-{/if}
		</div>
	</td>
</tr>
</table>
</div>

<br />

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td colspan="2">{translate key="reviewer.article.reviewSteps"}</td>
</tr>
<tr class="submissionRowAlt">
	<td class="enumeration" valign="top"><span class="boldText">1.</span></td><td class="submissionBox"><span class="boldText">{translate key="reviewer.article.reviewerInstruction1a"}{if $editor}, <a href="mailto:{$editor->getEditorEmail()}">{$editor->getEditorFullName()}</a>,{/if} {translate key="reviewer.article.reviewerInstruction1b"}.</span></td>
</tr>
<tr class="submissionRow">
	<td class="enumeration"></td><td class="submissionBox">
		{if not $confirmedStatus}
		<form method="post" action="{$requestPageUrl}/confirmReview">
		<input type="hidden" name="reviewId" value="{$submission->getReviewId()}" />
		<table class="plainFormat" width="100%" style="text-align: center"><tr>
			<td><input type="submit" name="acceptReview" value="{translate key="reviewer.article.canDoReview"}" /></td>
			<td><input type="submit" name="declineReview" value="{translate key="reviewer.article.cannotDoReview"}" /></td></tr>
		</table>
		</form>
		{else}
		<table class="plainFormat" width="100%">
		<tr>
		<td class="reviewLabelLong"><span class="boldText">{translate key="submission.response"}</span></td>
		<td><span class="boldTextAlt">
			{if not $declined}{translate key="submission.accepted"}{else}{translate key="submission.rejected"}{/if}
		</span></td>
		</tr>
		</table>
		{/if}
	</td>
</tr>
<tr class="submissionDivider">
        <td colspan="2"></td>
</tr>
{if $journal->getSetting('reviewGuidelines')}
{assign var="haveGuide" value=true}
<tr class="submissionRowAlt">
        <td class="enumeration" valign="top"><span class="boldText">2.</span></td><td class="submissionBox"><span class="boldText">{translate key="reviewer.article.reviewerInstruction2"}.</span></td>
</tr>
<tr class="submissionDivider">
	<td colspan="2"></td>
</tr>
{else}
{assign var="haveGuide" value=false}
{/if}
<tr class="submissionRowAlt">
	<td class="enumeration" valign="top"><span class="boldText">{if $haveGuide}3{else}2{/if}.</span></td><td class="submissionBox"><span class="boldText">{translate key="reviewer.article.reviewerInstruction3"}.</span></td>
</tr>
<tr class="submissionRow">
	<td class="enumeration"></td><td class="submissionBox">
		<table class="plainFormat">
			{if ($confirmedStatus and not $declined) or not $journal->getSetting('restrictReviewerFileAccess')}
			<tr>
				<td class="reviewLabelLong">
					<span class="boldText">{translate key="submission.submissionManuscript"}</span>
				</td>
				<td>
					{if $submission->getDateConfirmed() or not $journal->getSetting('restrictReviewerAccessToFile')}
						<a href="{$requestPageUrl}/downloadFile/{$submission->getReviewId()}/{$submission->getArticleId()}/{$reviewFile->getFileId()}/{$reviewFile->getRevision()}" class="file">{$reviewFile->getFileName()}</a>
					{else}{$reviewFile->getFileName()}{/if}
					&nbsp;&nbsp;{$reviewFile->getDateModified()|date_format:$dateFormatShort}
				</td>
			</tr>
			<tr>
				<td class="reviewLabelLong">
					<span class="boldText">{translate key="article.suppFiles"}</span>
				</td>
				<td>
					{foreach from=$suppFiles item=suppFile}
						<a href="{$requestPageUrl}/downloadFile/{$submission->getReviewId()}/{$submission->getArticleId()}/{$suppFile->getFileId()}" class="file">{$suppFile->getTitle()}</a><br />
					{foreachelse}
						{translate key="common.none"}
					{/foreach}
				</td>
			</tr>
			{else}
			<tr><td><span class="boldText">{translate key="reviewer.article.restrictedFileAccess"}.</span></td></tr>
			{/if}
		</table>
	</td>
</tr>
<tr class="submissionDivider">
	<td colspan="2"></td>
</tr>
<tr class="submissionRowAlt">
	<td class="enumeration" valign="top"><span class="boldText">{if $haveGuide}4{else}3{/if}.</span></td><td class="submissionBox"><span class="boldText">{translate key="reviewer.article.reviewerInstruction4a"}.</span></td>
</tr>
<tr class="submissionRow">
	<td class="enumeration"></td><td class="submissionBox">
			<span class="boldText">
			{translate key="submission.logType.review"} 
			{if $confirmedStatus and not $declined}
				<a href="javascript:openComments('{$requestPageUrl}/viewPeerReviewComments/{$submission->getArticleId()}/{$submission->getReviewId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>
			{else}
				<img src="{$baseUrl}/templates/images/letter.gif" border="0" />
			{/if}
			</span>
	</td>
</tr>
<tr class="submissionDivider">
	<td colspan="2"></td>
</tr>
<tr class="submissionRowAlt">
	<td class="enumeration" valign="top"><span class="boldText">{if $haveGuide}5{else}4{/if}.</span></td><td class="submissionBox"><span class="boldText">{translate key="reviewer.article.reviewerInstruction5"}.</span></td>
</tr>
<tr class="submissionRow">
	<td class="enumeration"></td><td class="submissionBox">
		<table class="plainFormat" width="100%">
			{foreach from=$submission->getReviewerFileRevisions() item=reviewerFile key=key}
				<tr>
				<td class="reviewLabelLong">
					{if $key eq "0"}
						<span class="boldText">{translate key="reviewer.article.uploadedFile"}</span>
					{/if}
				</td>
				<td>
					<div class="list">
						<a href="{$requestPageUrl}/downloadFile/{$submission->getReviewId()}/{$submission->getArticleId()}/{$reviewerFile->getFileId()}/{$reviewerFile->getRevision()}" class="file">{$reviewerFile->getFileName()}</a>
					</div>
					<div class="list">
						{$reviewerFile->getDateModified()|date_format:$dateFormatShort}
					</div>
					{if not $submission->getRecommendation()}
					<div class="list">
						<a href="{$requestPageUrl}/deleteReviewerVersion/{$submission->getReviewId()}/{$reviewerFile->getFileId()}/{$reviewerFile->getRevision()}">DELETE</a>
					</div>
					{/if}
				</td>
				</tr>
			{foreachelse}
				<tr>
				<td class="reviewLabelLong">
					<span class="boldText">{translate key="reviewer.article.uploadedFile"}</span>
				</td>
				<td>
					{translate key="common.none"}
				</td>
				</tr>
			{/foreach}
		</table>
		{if not $submission->getRecommendation()}
			<div class="indented">
				<form method="post" action="{$requestPageUrl}/uploadReviewerVersion" enctype="multipart/form-data">
				<input type="hidden" name="reviewId" value="{$submission->getReviewId()}" />
				<input type="file" name="upload" style="width: 21em" {if not $confirmedStatus or $declined}disabled="disabled"{/if} />
				<input type="submit" name="submit" value="{translate key="common.upload"}" {if not $confirmedStatus or $declined}disabled="disabled"{/if} />
				</form>
			</div>
			<table class="plainFormat" width="100%">
				<tr>
				<td valign="top">{translate key="common.note"}:</td>
				<td >
					{translate key="reviewer.article.noteOnUploads"}
					<a href="http://economics.ca/cje/en/pdfclean.php">
					{translate key="reviewer.article.anonymization"}</a>.
				</td>
				</tr>
			</table>
		{/if}
	</td>
</tr>
<tr class="submissionDivider">
	<td colspan="2"></td>
</tr>
<tr class="submissionRowAlt">
	<td class="enumeration" valign="top"><span class="boldText">{if $haveGuide}6{else}5{/if}.</span></td><td class="submissionBox"><span class="boldText">{translate key="reviewer.article.reviewerInstruction6"}.</span></td>
</tr>
<tr class="submissionRow">
	<td class="enumeration"></td><td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td class="reviewLabelLong"><span class="boldText">{translate key="submission.recommendation"}</span></td>
				<td>
				{if $submission->getRecommendation()}
					{assign var="recommendation" value=$submission->getRecommendation()}
					<span class="boldTextAlt">{translate key=$reviewerRecommendationOptions.$recommendation}</span>
				{else}
					<form method="post" action="{$requestPageUrl}/recordRecommendation">
					<input type="hidden" name="reviewId" value="{$submission->getReviewId()}" />
					<select name="recommendation" {if not $confirmedStatus or $declined}disabled="disabled"{/if}>
						<option value="1">{translate key="reviewer.article.decision.accept"}</option>
						<option value="2">{translate key="reviewer.article.decision.pendingRevisions"}</option>
						<option value="3">{translate key="reviewer.article.decision.resubmitHere"}</option>
						<option value="4">{translate key="reviewer.article.decision.resubmitElsewhere"}</option>
						<option value="5">{translate key="reviewer.article.decision.decline"}</option>
						<option value="6">{translate key="reviewer.article.decision.seeComments"}</option>
					</select>
					</td><td>
					<input type="submit" name="submit" value="{translate key="reviewer.article.submitReview"}" {if not $confirmedStatus or $declined}disabled="disabled"{/if} />
					</form>					
				{/if}
				</td>		
			</tr>
		</table>
	</td>
</tr>
</table>
</div>

{if $journal->getSetting('reviewGuidelines')}
<br />

<div class="tableContainer">
<table width="100%">
	<tr class="heading">
		<td>{translate key="reviewer.article.reviewerGuidelines"}</td>
	</tr>
	<tr class="submissionRow">
		<td style="padding: 0 2em 0 2em"><span class="boldText">{$journal->getSetting('reviewGuidelines')|nl2br}</span>	</td>
	</tr>
</table>
</div>
{/if}

{include file="common/footer.tpl"}



