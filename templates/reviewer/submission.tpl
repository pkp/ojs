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

<div class="tableContainer">
<table width="100%">
<tr class="submissionRow">
	<td class="submissionBox">
		<div class="leftAligned">
			<div>{foreach from=$submission->getAuthors() item=author key=authorKey}{if $authorKey neq 0},{/if} {$author->getFullName()}{/foreach}</div>
			<div class="submissionTitle">{$submission->getArticleTitle()}</div>
		</div>
		<div class="submissionId">{$submission->getArticleId()}</div>
	</td>
</tr>
</table>
</div>

<br />

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.submission"}</td>
</tr>
<tr>
	<td>
		<table class="plain" width="100%">
			<tr>
				<td valign="top">{translate key="article.indexingInformation"}: <a href="{$pageUrl}/sectionEditor/viewMetadata/{$submission->getArticleId()}">{translate key="article.metadata"}</a></td>
				<td valign="top">{translate key="article.section"}: {$submission->getSectionTitle()}</td>
			</tr>
			<tr>
				<td colspan="2">
					{translate key="reviewer.article.fileToBeReviewed"}:
					{if $reviewFile}
						<a href="{$pageUrl}/reviewer/downloadFile/{$reviewFile->getFileId()}" class="file">{$reviewFile->getFileName()}</a> {$reviewFile->getDateModified()|date_format:$dateFormatShort}</td>
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
									<a href="{$pageUrl}/reviewer/downloadFile/{$suppFile->getFileId()}">{$suppFile->getTitle()}</a><br />
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
								<form method="post" action="{$pageUrl}/reviewer/confirmReview">
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
			<tr>
				<td colspan="2">{translate key="reviewer.article.submissionEditor"}: <a href="mailto:{$editor->getEditorEmail()}">{$editor->getEditorFullName()}</a></td>
			</tr>			
		</table>
	</td>
</tr>
</table>
</div>

<br />

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.peerReview"}</td>
</tr>
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td width="40%"></td>
				<td width="60%">
					<table class="plainFormat" width="100%">
						<tr>
							<td align="center"><strong>{translate key="submission.request"}</strong></td>
							<td align="center"><strong>{translate key="submission.acceptance"}</strong></td>
							<td align="center"><strong>{translate key="submission.due"}</strong></td>
							<td align="center"><strong>{translate key="submission.thank"}</strong></td>						</tr>
						</tr>
						<tr>
							<td align="center">{if $submission->getDateNotified()}{$submission->getDateNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
							<td align="center">{if $submission->getDateConfirmed()}{$submission->getDateConfirmed()|date_format:$dateFormatShort}{else}-{/if}</td>
							<td align="center">{if $submission->getDateDue()}{$submission->getDateDue()|date_format:$dateFormatShort}{else}-{/if}</td>
							<td align="center">{if $submission->getDateAcknowledged()}{$submission->getDateAcknowledged()|date_format:$dateFormatShort}{else}-{/if}</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</td>
</tr>
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
						<form method="post" action="{$pageUrl}/reviewer/recordRecommendation">
							<input type="hidden" name="reviewId" value="{$submission->getReviewId()}">
							<select name="recommendation" {if not $confirmedStatus}disabled="disabled"{/if}>
								<option value="2">Accept</option>
								<option value="3">Accept with revisions</option>
								<option value="4">Resubmit for review</option>
								<option value="5">Resubmit elsewhere</option>
								<option value="6">Decline</option>
								<option value="7">See comments</option>
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
					<a href="#">...</a>
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
					<a href="{$pageUrl}/sectionEditor/downloadFile/{$reviewerFile->getFileId()}" class="file">{$reviewerFile->getFileName()}</a> {$reviewerFile->getDateModified()|date_format:$dateFormatShort}
				</td>
			</tr>
			{/foreach}
			<tr>
				<td></td>
				<td>
					<div class="indented">
						<form method="post" action="{$pageUrl}/reviewer/uploadReviewerVersion" enctype="multipart/form-data">
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
		</table>
	</td>
</tr>
</table>
</div>
{include file="common/footer.tpl"}
