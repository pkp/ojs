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

<div class="formSectionTitle">{translate key="submission.submission"}</div>
<div class="formSection">
<table class="form" width="100%">
<tr>
	<td class="formLabel">{translate key="article.title"}:</td>
	<td>{$submission->getTitle()}</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.authors"}:</td>
	<td>
		{foreach from=$submission->getAuthors() item=author}
			<div>{$author->getFullName()}</div>
		{/foreach}
	</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.indexingInformation"}:</td>
	<td>[<a href="{$pageUrl}/reviewer/viewMetadata/{$submission->getArticleId()}">{translate key="article.metadata"}</a>]</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.section"}:</td>
	<td>{$submission->getSectionTitle()}</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.file"}:</td>
	<td>
		{if $submissionFile}
			<a href="{$pageUrl}/reviewer/downloadFile?fileId={$submissionFile->getFileId()}">{$submissionFile->getFileName()}</a> {$submissionFile->getDateModified()|date_format:$dateFormatShort}</td>
		{/if}
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.suppFiles"}:</td>
	<td>
		{foreach from=$suppFiles item=suppFile}
			<div><a href="{$pageUrl}/reviewer/downloadFile?fileId={$suppFile->getFileId()}">{$suppFile->getTitle()}</a></div>
		{foreachelse}
			<div>{translate key="common.none"}</div>
		{/foreach}
	</td>
	<td align="right"></td>
</tr>
{if not $confirmedStatus}
<tr>
	<td class="formLabel">{translate key="submission.notifyTheEditor"}:<br />(before d/m/y)</td>
	<td colspan="2">
	<form method="post" action="{$pageUrl}/reviewer/confirmReview">
		<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
		<input type="submit" name="acceptReview" value="{translate key="submission.canDoReview"}">
		<input type="submit" name="declineReview" value="{translate key="submission.cannotDoReview"}">
	</form>
	</td>
</tr>
{/if}
<tr>
	<td class="formLabel">{translate key="submission.submissionEditor"}:</td>
	<td><a href="mailto:{$editor->getEmail()}">{$editor->getFullName()}</a></td>
	<td></td>
</tr>
</table>
</div>

<br />
<br />

<div class="formSectionTitle">{translate key="submission.peerReview"}</div>
<div class="formSection">
<table class="plain" width="100%">
<tr>
	<td class="label" width="40%">&nbsp;</td>
	<td class="label" width="15%">{translate key="submission.request"}</td>
	<td class="label" width="15%">{translate key="submission.accept"}</td>
	<td class="label" width="15%">{translate key="submission.due"}</td>
	<td class="label" width="15%">{translate key="submission.thank"}</td>
</tr>
<tr>
	<td width="40%">&nbsp;</td>
	<td width="15%">{$reviewAssignment->getDateNotified()|date_format:$dateFormatShort}</td>
	<td width="15%">{$reviewAssignment->getDateConfirmed()|date_format:$dateFormatShort}</td>
	<td width="15%">{$reviewAssignment->getDateDue()|date_format:$dateFormatShort}</td>
	<td width="15%">{$reviewAssignment->getDateAcknowledged()|date_format:$dateFormatShort}</td>
</tr>
</table>
<table class="plain" width="100%">
<tr>
	<td>{translate key="submission.reviewerCommentsDescription"}:</td>
	<td>[<a href="">{translate key="submission.reviewerComments"}</a>]</td>	
	<td></td>
</tr>
<tr>
	<td>{translate key="submission.selectRecommendation"}:</td>
	<td>
		{if not $reviewAssignment->getRecommendation()}
			<form method="post" action="{$pageUrl}/reviewer/recordRecommendation">
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
				<select name="recommendation" {if not $confirmedStatus}disabled="disabled"{/if}>
					<option value="2">Accept</option>
					<option value="3">Accept with revisions</option>
					<option value="4">Resubmit for review</option>
					<option value="5">Resubmit elsewhere</option>
					<option value="6">Decline</option>
					<option value="7">See comments</option>
				</select>
				<input type="submit" name="submit" value="{translate key="submission.submitReview"}" {if not $confirmedStatus}disabled="disabled"{/if}>
			</form>
		{else}
			<b>{$reviewAssignment->getRecommendation()}</b>
		{/if}
	</td>
</tr>

<form method="post" action="">
<tr>
	<td>{translate key="submission.reviewersAnnotatedVersion"}:</td>
	<td>
		<input type="file" name="anotatedFile" {if not $confirmedStatus}disabled="disabled"{/if}>
		<input type="submit" name="submit" value="{translate key="common.upload"}" {if not $confirmedStatus}disabled="disabled"{/if}></td>
</tr>
<tr>
	<td></td>
	<td>{translate key="submission.reviewersAnnotatedVersionDescription"}</td>
</tr>
</form>
</table>
</div>
{include file="common/footer.tpl"}
