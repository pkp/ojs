{**
 * peerReview.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the peer review table.
 *
 * $Id$
 *}

<h4>{translate key="submission.reviewVersion"}</h4>

<table width="100%" class="data">
<tr>
	<td colspan="2">
		<form method="post" action="{$requestPageUrl}/designateReviewVersion">
			{translate key="editor.article.designateReviewVersion"}
			<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
			<input type="checkbox" name="designate" value="1" /> 
			<input type="submit" value="{translate key="common.record"}" />
		</form>
	</td>
</tr>
<tr>
	<td colspan="2">
		<form method="post" action="{$requestPageUrl}/uploadReviewVersion" enctype="multipart/form-data">
			{translate key="editor.article.uploadReviewVersion"}
			<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
			<input type="file" name="upload" />
			<input type="submit" name="submit" value="{translate key="common.upload"}" />
		</form>
	</td>
</tr>
<tr>
	<td class="label" width="20%">{translate key="editor.article.reviewVersion"}</td>
	<td width="80%">
		{if $reviewFile}
			<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$reviewFile->getFileId()}/{$reviewFile->getRevision()}" class="file">{$reviewFile->getFileName()}</a> {$reviewFile->getDateModified()|date_format:$dateFormatShort}
		{else}
			{translate key="common.none"}
		{/if}
	</td>
</tr>
<tr>
	<td class="label">{translate key="article.suppFiles"}</td>
	<td>
		{foreach from=$suppFiles item=suppFile}
			<form method="post" action="{$requestPageUrl}/uploadReviewVersion">
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$suppFile->getFileId()}/{$suppFile->getRevision()}" class="file">{$suppFile->getFileName()}</a> {$suppFile->getDateModified()|date_format:$dateFormatShort}
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
				<input type="hidden" name="fileId" value="{$suppFile->getFileId()}" />
				<input type="hidden" name="revision" value="{$suppFile->getRevision()}" />
				{translate key="editor.article.hideSuppFile"}
				<input type="checkbox" name="hide" value="1" />
				<input type="submit" name="submit" value="{translate key="common.record"}" />
			</form>
		{foreachelse}
			{translate key="common.none"}
		{/foreach}
	</td>
</tr>
</table>

<div class="separator"></div>

<h3>{translate key="submission.peerReview"}</h3>
<a name="peerReview"></a>
<table width="100%" class="data">
<tr>
	<td>{translate key="submission.round" round=$round}</td>
	<td><a href="{$requestPageUrl}/selectReviewer/{$submission->getArticleId()}" class="action">{translate key="editor.article.selectReviewer"}</a></td>
	<td><a href="##" class="action">{translate key="editor.article.viewRegrets"}</a></td>
</tr>
</table>

{assign var="start" value="A"|ord} 
{assign var="numReviewAssignments" value=$reviewAssignments|@count} 
{foreach from=$reviewAssignments item=reviewAssignment key=reviewKey}
{assign var="reviewId" value=$reviewAssignment->getReviewId()}
<div class="separator"></div>

<table class="data">
<tr>
	<td><h4>{translate key="user.role.reviewer"} {$reviewKey+$start|chr} {$reviewAssignment->getReviewerFullName()}</h4></td>
	<td>
		{if not $reviewAssignment->getDateInitiated()}
			<form method="post" action="{$requestPageUrl}/removeReview">
				<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
				<input type="submit" value="{translate key="editor.article.clearReview"}">
			</form>
		{elseif $reviewAssignment->getDateInitiated()}
			<form method="post" action="{$requestPageUrl}/cancelReview">
				<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
				<input type="submit" value="{translate key="editor.article.cancelReview"}">
			</form>
		{/if}
	</td>
</tr>
</table>

<table width="100%" class="data">
<tr>
	<td class="label" width="20%">{translate key="submission.schedule"}</td>
	<td width="80%">
		<table width="100%" class="data">
			<tr>
				<td width="25%"><span class="scheduleLabel">{translate key="submission.request"}</span></td>
				<td width="25%"><span class="scheduleLabel">{translate key="submission.acceptance"}</span></td>
				<td width="25%"><span class="scheduleLabel"><a href="{$requestPageUrl}/setDueDate/{$reviewAssignment->getArticleId()}/{$reviewAssignment->getReviewId()}">{translate key="submission.due"}</a></span></td>
				<td width="25%"><span class="scheduleLabel">{translate key="submission.thank"}</span></td>
			</tr>
			<tr>
				<td>
					{if $reviewAssignment->getDateNotified()}
						{$reviewAssignment->getDateNotified()|date_format:$dateFormatShort}
					{else}
						<form method="post" action="{$requestPageUrl}/notifyReviewer">
							<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="image" src="{$baseUrl}/templates/images/mail.gif">
						</form>
					{/if}
				</td>
				<td>{if $reviewAssignment->getDateConfirmed()}{$reviewAssignment->getDateConfirmed()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td>{if $reviewAssignment->getDateDue()}{$reviewAssignment->getDateDue()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td>
					{if $reviewAssignment->getDateAcknowledged()}
						{$reviewAssignment->getDateAcknowledged()|date_format:$dateFormatShort}
					{else}
						<form method="post" action="{$requestPageUrl}/thankReviewer">
							<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="image" src="{$baseUrl}/templates/images/mail.gif">
						</form>
					{/if}
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr>
	<td class="label">{translate key="reviewer.article.recommendation"}</td>
	<td>
		{if $reviewAssignment->getRecommendation()}
			{assign var="recommendation" value=$reviewAssignment->getRecommendation()}
			{translate key=$reviewerRecommendationOptions.$recommendation}
		{else}
			{translate key="common.none"}
		{/if}
	</td>
</tr>
<tr>
	<td class="label">{translate key="reviewer.article.reviewerComments"}</td>
	<td>
		{if $reviewAssignment->getMostRecentPeerReviewComment()}
			{assign var="comment" value=$reviewAssignment->getMostRecentPeerReviewComment()}
			<a href="javascript:openComments('{$requestPageUrl}/viewPeerReviewComments/{$submission->getArticleId()}/{$reviewAssignment->getReviewId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a> {$comment->getDatePosted()|date_format:$dateFormatShort}
		{else}
			<a href="javascript:openComments('{$requestPageUrl}/viewPeerReviewComments/{$submission->getArticleId()}/{$reviewAssignment->getReviewId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>
		{/if}
	</td>
</tr>
<tr>
	<td class="label">{translate key="reviewer.article.uploadedFile"}</td>
	<td>
		<table width="100%" class="data">
			{foreach from=$reviewAssignment->getReviewerFileRevisions() item=reviewerFile key=key}
			<tr>
				<td valign="middle">
					<form name="authorView{$reviewAssignment->getReviewId()}" method="post" action="{$requestPageUrl}/makeReviewerFileViewable">
						<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$reviewerFile->getFileId()}/{$reviewerFile->getRevision()}" class="file">{$reviewerFile->getFileName()}</a> {$reviewerFile->getDateModified()|date_format:$dateFormatShort}
						<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
						<input type="hidden" name="fileId" value="{$reviewerFile->getFileId()}">
						<input type="hidden" name="revision" value="{$reviewerFile->getRevision()}">
						{translate key="editor.article.showAuthor"} <input type="checkbox" name="viewable" value="1" {if $reviewerFile->getViewable()}checked="checked"{/if}>
						<input type="submit" value="{translate key="common.record"}">
					</form>
				</td>
			</tr>
			{foreachelse}
			<tr>
				<td>{translate key="common.none"}</td>
			</tr>
			{/foreach}
		</table>
	</td>
</tr>
{if $rateReviewerOnTimeliness or $rateReviewerOnQuality}
<tr>
	<td class="label">{translate key="editor.article.timeliness"}</td>
	<td>
	<form method="post" action="{$requestPageUrl}/rateReviewer">
		<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
		<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
		<tr>
			{if $rateReviewerOnTimeliness}
			<td>
				<table class="plainFormat">
					<tr>
						<td align="right">
							<span class="boldText">{translate key="editor.article.timeliness"}</span>
						</td>
						<td>
							<select name="timeliness"{if not $reviewAssignment->getRecommendation()} disabled=DISABLED{/if}>
							{html_options_translate options=$reviewerRatingOptions selected=$reviewAssignment->getTimeliness()}
							</select>
						</td>
					</tr>
				</table>
			</td>
			{/if}
			{if $rateReviewerOnQuality}
			<td>
				<table class="plainFormat">
					<tr>
						<td align="right">
							<span class="boldText">{translate key="editor.article.quality"}</span>
						</td>
						<td>
							<select name="quality"{if not $reviewAssignment->getRecommendation()} disabled=DISABLED{/if}>
							{html_options_translate options=$reviewerRatingOptions selected=$reviewAssignment->getQuality()}
							</select>
						</td>
					</tr>
				</table>
			</td>
			{/if}
			<td width="40%">
				<table class="plainFormat">
					<tr>
						<td>
							<input type="submit" value="{translate key="common.record"}"{if not $reviewAssignment->getRecommendation()} disabled=DISABLED{/if}>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</form>
	</td>
</tr>
{/if}
</table>
{/foreach}