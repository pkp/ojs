{**
 * submission.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the reviewer administration page.
 *
 * FIXME: At "Notify The Editor", fix the date.
 * FIXME: Recommendation options are not localized, and only output numbers.
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
	<td>{translate key="submission.submissionToBeReviewed"}</td>
</tr>
<tr class="submissionRow">
	<td>
		<table class="plainFormat" width="100%">
		<!--	<tr>
				<td valign="top">{translate key="article.indexingInformation"}: <a href="{$requestPageUrl}/viewMetadata/{$submission->getArticleId()}">{translate key="article.metadata"}</a></td>
				<td valign="top">{translate key="article.section"}: {$submission->getSectionTitle()}</td> 
			</tr>	
		-->
			<tr>
				<td class="reviewLabel"><span class="boldText">{translate key="article.title"}</span></td>
				<td><span class="submissionTitle">{$submission->getArticleTitle()}</span></td>
			</tr>
			<tr>
				<td class="reviewLabel"><span class="boldText">{translate key="article.journalSection"}</span></td>
				<td>{$submission->getSectionTitle()}</td>
			</tr>
			<tr>
				<td class="reviewLabel" valign="top"><span class="boldText">{translate key="submission.abstract"}</span></td>
				<td>{$submission->getArticleAbstract()}</td>
			</tr>
		<!--
			<tr>
				<td colspan="2">
					{translate key="reviewer.article.fileToBeReviewed"}:
					{if $reviewFile}
						<a href="{$requestPageUrl}/downloadFile/{$submission->getReviewId()}/{$submission->getArticleId()}/{$reviewFile->getFileId()}/{$reviewFile->getRevision()}" class="file">{$reviewFile->getFileName()}</a> {$reviewFile->getDateModified()|date_format:$dateFormatShort}</td>
					{else}
						{translate key="common.none"}
					{/if}
				</td>
			</tr>
			<tr>
				<td valign="top" colspan="2">
					<table class="plainFormat">
						<tr>
							<td valign="top">{translate key="article.suppFiles"}:</td>
							<td valign="top">
								{foreach from=$suppFiles item=suppFile}
									<a href="{$requestPageUrl}/downloadFile/{$submission->getReviewId()}/{$submission->getArticleId()}/{$suppFile->getFileId()}" class="file">{$suppFile->getTitle()}</a><br />
								{foreachelse}
									{translate key="common.none"}
								{/foreach}
							</td>
						</tr>
					</table>
				</td>
			</tr>
			{if not $confirmedStatus}
			<tr>
				<td valign="top" colspan="2">
					<table class="plainFormat">
						<tr>
							<td valign="top">{translate key="reviewer.article.notifyTheEditor"}:<br />(before d/m/y)</td>
							<td>
								<form method="post" action="{$requestPageUrl}/confirmReview">
									<input type="hidden" name="reviewId" value="{$submission->getReviewId()}">
									<input type="submit" name="acceptReview" value="{translate key="reviewer.article.canDoReview"}">
									<input type="submit" name="declineReview" value="{translate key="reviewer.article.cannotDoReview"}">
								</form>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			{/if}
		-->
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
	<td>{translate key="submission.reviewSchedule"}</td>
</tr>
<tr class="submissionRow">
	<td class="submissionBox" style="text-align: center">
		<div class="spacedList">
			<strong>{translate key="submission.request"}</strong><br />
			{if $submission->getDateNotified()}{$submission->getDateNotified()|date_format:$dateFormatShort}{else}-{/if}
		</div>
		<div class="spacedList">
			<strong>{translate key="submission.response"}</strong><br />
			{if $submission->getDateConfirmed()}{$submission->getDateConfirmed()|date_format:$dateFormatShort}{else}-{/if}
		</div>
		<div class="spacedList">
			<strong>{translate key="submission.due"}</strong><br />
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
	<td colspan="2">{translate key="submission.reviewSteps"}</td>
</tr>
<tr class="submissionRowAlt">
	<td class="enumeration">1.</td><td class="submissionBox">{translate key="submission.reviewerInstruction1a"}{if $editor}, <a href="mailto:{$editor->getEditorEmail()}">{$editor->getEditorFullName()}</a>,{/if} {translate key="submission.reviewerInstruction1b"}.</td>
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
<tr class="submissionRowAlt">
	<td class="enumeration">2.</td><td class="submissionBox">{translate key="submission.reviewerInstruction2"}.</td>
</tr>
<tr class="submissionRow">
	<td class="enumeration"></td><td class="submissionBox">
		<table class="plainFormat">
			<tr>
				<td class="reviewLabelLong">
					<span class="boldText">{translate key="submission.submissionManuscript"}</span>
				</td>
				<td>
					<a href="{$requestPageUrl}/downloadFile/{$submission->getReviewId()}/{$submission->getArticleId()}/{$reviewFile->getFileId()}/{$reviewFile->getRevision()}" class="file">{$reviewFile->getFileName()}</a> 
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
		</table>
	</td>
</tr>
<tr class="submissionDivider">
	<td colspan="2"></td>
</tr>
<tr class="submissionRowAlt">
	<td class="enumeration">3.</td><td class="submissionBox">{translate key="submission.reviewerInstruction3"}.</td>
</tr>
<tr class="submissionRow">
	<td class="enumeration"></td><td class="submissionBox">
		{if $submission->getMostRecentPeerReviewComment()}
			{assign var="comment" value=$submission->getMostRecentPeerReviewComment()}
			<a href="javascript:openComments('{$requestPageUrl}/viewPeerReviewComments/{$submission->getArticleId()}/{$submission->getReviewId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /> {translate key="submission.clickHereToLeaveComments"}</a>
			(last comment left on {$comment->getDatePosted()|date_format:$dateFormatShort})
		{else}
			<a href="javascript:openComments('{$requestPageUrl}/viewPeerReviewComments/{$submission->getArticleId()}/{$submission->getReviewId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /> {translate key="submission.clickHereToLeaveComments"}</a>
		{/if}
	</td>
</tr>
<tr class="submissionDivider">
	<td colspan="2"></td>
</tr>
<tr class="submissionRowAlt">
	<td class="enumeration">4.</td><td class="submissionBox">{translate key="submission.reviewerInstruction4"}.</td>
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
					<div class="list">
						<a href="{$requestPageUrl}/deleteReviewerVersion/{$submission->getReviewId()}/{$reviewerFile->getFileId()}/{$reviewerFile->getRevision()}">DELETE</a>
					</div>
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
				<input type="file" name="upload" style="width: 21em" {if not $confirmedStatus}disabled="disabled"{/if} />
				<input type="submit" name="submit" value="{translate key="common.upload"}" {if not $confirmedStatus}disabled="disabled"{/if} />
				</form>
			</div>
			<table class="plainFormat" width="100%">
				<tr>
				<td valign="top">{translate key="submission.notes.note"}:</td>
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
	<td class="enumeration">5.</td><td class="submissionBox">{translate key="submission.reviewerInstruction5"}.</td>
</tr>
<tr class="submissionRow">
	<td class="enumeration"></td><td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td class="reviewLabelLong"><span class="boldText">{translate key="submission.recommendation"}</span></td>
				<td class="reviewResult">
				{if $submission->getRecommendation()}
					{assign var="recommendation" value=$submission->getRecommendation()}
					<span class="boldTextAlt">{translate key=$reviewerRecommendationOptions.$recommendation}</span>
				{else}
					<form method="post" action="{$requestPageUrl}/recordRecommendation">
					<input type="hidden" name="reviewId" value="{$submission->getReviewId()}" />
					<select name="recommendation" {if not $confirmedStatus}disabled="disabled"{/if}>
						<option value="1">Accept</option>
						<option value="2">Accept with revisions</option>
						<option value="3">Resubmit for review</option>
						<option value="4">Resubmit elsewhere</option>
						<option value="5">Decline</option>
						<option value="6">See comments</option>
					</select>
					</td><td>
					<input type="submit" name="submit" value="{translate key="reviewer.article.submitReview"}" {if not $confirmedStatus}disabled="disabled"{/if} />
					</form>					
				{/if}
				</td>		
			</tr>
		</table>
	</td>
</tr>
<!--
<tr class="submissionRow">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td class="reviewLabel">
					<span class="boldText">{translate key="reviewer.article.recommendation"}</span>
				</td>
				<td>
					{if $submission->getRecommendation()}
						{assign var="recommendation" value=$submission->getRecommendation()}
						<span class="boldTextAlt">{translate key=$reviewerRecommendationOptions.$recommendation}</span>
					{else}
						<form method="post" action="{$requestPageUrl}/recordRecommendation">
							<input type="hidden" name="reviewId" value="{$submission->getReviewId()}">
							<select name="recommendation" {if not $confirmedStatus}disabled="disabled"{/if}>
								<option value="1">Accept</option>
								<option value="2">Accept with revisions</option>
								<option value="3">Resubmit for review</option>
								<option value="4">Resubmit elsewhere</option>
								<option value="5">Decline</option>
								<option value="6">See comments</option>
							</select>
							<input type="submit" name="submit" value="{translate key="reviewer.article.submitReview"}" {if not $confirmedStatus}disabled="disabled"{/if}>
						</form>
					{/if}
				</td>
			</tr>
			<tr>
				<td class="reviewLabel">
					<span class="boldText">{translate key="reviewer.article.reviewerComments"}</span>
				</td>
				<td>
					{if $submission->getMostRecentPeerReviewComment()}
						{assign var="comment" value=$submission->getMostRecentPeerReviewComment()}
						<a href="javascript:openComments('{$requestPageUrl}/viewPeerReviewComments/{$submission->getArticleId()}/{$submission->getReviewId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>{$comment->getDatePosted()|date_format:$dateFormatShort}
					{else}
						<a href="javascript:openComments('{$requestPageUrl}/viewPeerReviewComments/{$submission->getArticleId()}/{$submission->getReviewId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>
					{/if}
				</td>
			</tr>
			{foreach from=$submission->getReviewerFileRevisions() item=reviewerFile key=key}
			<tr>
				<td class="reviewLabel">
					{if $key eq "0"}
						<span class="boldText">{translate key="reviewer.article.uploadedFile"}</span>
					{/if}
				</td>
				<td>
					<a href="{$requestPageUrl}/downloadFile/{$submission->getReviewId()}/{$submission->getArticleId()}/{$reviewerFile->getFileId()}/{$reviewerFile->getRevision()}" class="file">{$reviewerFile->getFileName()}</a> {$reviewerFile->getDateModified()|date_format:$dateFormatShort}
				</td>
			</tr>
			{foreachelse}
				<td class="reviewLabel">
					<span class="boldText">{translate key="reviewer.article.uploadedFile"}</span>
				</td>
				<td>
					{translate key="common.none"}
				</td>
			{/foreach}
			{if not $submission->getRecommendation()}
			<tr>
				<td></td>
				<td>
					<div class="indented">
						<form method="post" action="{$requestPageUrl}/uploadReviewerVersion" enctype="multipart/form-data">
							<input type="hidden" name="reviewId" value="{$submission->getReviewId()}" />
							<input type="file" name="upload" {if not $confirmedStatus}disabled="disabled"{/if} />
							<input type="submit" name="submit" value="{translate key="common.upload"}" {if not $confirmedStatus}disabled="disabled"{/if} />
						</form>
					</div>
				</td>
			</tr>
			<tr>
				<td></td>
				<td>
					<div class="indented">
						{translate key="reviewer.article.reviewersAnnotatedVersionDescription"}
					</div>
				</td>
			</tr>
			{/if}
		</table>
	</td>
</tr>
-->
</table>
</div>
{include file="common/footer.tpl"}



