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

<h3>{translate key="submission.reviewVersion"}</h3>

<table width="100%" class="data">
<tr valign="top">
	<td colspan="3">
		<form method="post" action="{$requestPageUrl}/designateReviewVersion">
			<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
			{if $submission->getSubmissionFile()}
				<label for="reviewDesignate">{translate key="editor.article.designateReviewVersion"}</label>
				<input type="checkbox" name="designate" id="reviewDesignate" value="1" /> 
				<input type="submit" value="{translate key="common.record"}" class="button" />
			{else}
				{translate key="editor.article.designateReviewVersion"}
				<input type="checkbox" disabled="disabled" name="designate" value="1" /> 
				<input type="submit" disabled="disabled" value="{translate key="common.record"}" class="button" />
			{/if}
		</form>
	</td>
</tr>
<tr valign="top">
	<td colspan="3">
		<form method="post" action="{$requestPageUrl}/uploadReviewVersion" enctype="multipart/form-data">
			{translate key="editor.article.uploadReviewVersion"}
			<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
			<input type="file" name="upload" class="uploadField" />
			<input type="submit" name="submit" value="{translate key="common.upload"}" class="button" />
		</form>
	</td>
</tr>
<tr valign="top">
	<td class="label" width="20%">{translate key="editor.article.reviewVersion"}</td>
	{if $reviewFile}
		<td width="15%" class="value">
			<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$reviewFile->getFileId()}/{$reviewFile->getRevision()}" class="file">{$reviewFile->getFileName()}</a>
		</td>
		<td width="65%" class="value">
			{$reviewFile->getDateModified()|date_format:$dateFormatShort}
		</td>
	{else}
		<td colspan="2" width="80%">{translate key="common.none"}</td>
	{/if}
</tr>
{foreach from=$suppFiles item=suppFile}
	<form method="post" action="{$requestPageUrl}/uploadReviewVersion">
	<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
	<input type="hidden" name="fileId" value="{$suppFile->getFileId()}" />
	<input type="hidden" name="revision" value="{$suppFile->getRevision()}" />

	<tr valign="top">
		{if !$notFirstSuppFile}
			<td class="label" rowspan="{$suppFiles|@count}">{translate key="article.suppFiles"}</td>
			{assign var=notFirstSuppFile value=1}
		{/if}
		<td width="15%" class="value">
			<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$suppFile->getFileId()}/{$suppFile->getRevision()}" class="file">{$suppFile->getFileName()}</a>
		</td>
		<td width="65%" class="value">
			{$suppFile->getDateModified()|date_format:$dateFormatShort}
			{translate key="editor.article.hideSuppFile"}
			<input type="checkbox" name="hide" value="1" />
			<input type="submit" name="submit" value="{translate key="common.record"}" class="button" />
		</td>
	</tr>
	</form>
{foreachelse}
	<tr valign="top">
		<td class="label">{translate key="article.suppFiles"}</td>
		<td colspan="2">{translate key="common.none"}</td>
	</tr>
{/foreach}
</table>

<div class="separator"></div>

<h3>{translate key="submission.peerReview"}</h3>
<a name="peerReview"></a>

<h4>{translate key="submission.round" round=$round}</h4>

<p>
	<a href="{$requestPageUrl}/selectReviewer/{$submission->getArticleId()}" class="action">{translate key="editor.article.selectReviewer"}</a>&nbsp;&nbsp;&nbsp;&nbsp;
	<a href="{$requestPageUrl}/submissionRegrets/{$submission->getArticleId()}" class="action">{translate key="sectionEditor.regrets.link"}</a>
</p>

{assign var="start" value="A"|ord} 
{foreach from=$reviewAssignments item=reviewAssignment key=reviewKey}
{assign var="reviewId" value=$reviewAssignment->getReviewId()}
{if not $reviewAssignment->getCancelled()}
	<div class="separator"></div>

	<table class="data">
	<tr>
		<td><h4>{translate key="user.role.reviewer"} {$reviewKey+$start|chr} {$reviewAssignment->getReviewerFullName()}</h4></td>
		<td>
			{if not $reviewAssignment->getDateNotified()}
				<a href="{$requestPageUrl}/clearReview/{$submission->getArticleId()}/{$reviewAssignment->getReviewId()}" class="action">{translate key="editor.article.clearReview"}</a>
			{else}
				<a href="{$requestPageUrl}/cancelReview?articleId={$submission->getArticleId()}&reviewId={$reviewAssignment->getReviewId()}" class="action">{translate key="editor.article.cancelReview"}</a>
			{/if}
		</td>
	</tr>
	</table>

	<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="20%">{translate key="submission.schedule"}</td>
		<td width="80%">
			<table width="100%" class="info">
				<tr>
					<td class="heading" width="25%">{translate key="submission.request"}</td>
					<td class="heading" width="25%">{translate key="submission.acceptance"}</td>
					<td class="heading" width="25%">{translate key="submission.due"}</td>
					<td class="heading" width="25%">{translate key="submission.thank"}</td>
				</tr>
				<tr valign="top">
					<td>
						{if $reviewAssignment->getDateNotified()}
							{$reviewAssignment->getDateNotified()|date_format:$dateFormatShort}
						{elseif ($reviewAssignment->getReviewFileId())}
							{icon name="mail" url="`$requestPageUrl`/beginReviewerRequest/`$submission->getArticleId()`/`$reviewAssignment->getReviewId()`"}
						{else}
							{icon name="mail" disabled="disabled" url="`$requestPageUrl`/notifyReviewer?reviewId=`$reviewAssignment->getReviewId()`&articleId=`$submission->getArticleId()`"}
						{/if}
					</td>
					<td>{if $reviewAssignment->getDateConfirmed()}{$reviewAssignment->getDateConfirmed()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
					<td><a href="{$requestPageUrl}/setDueDate/{$reviewAssignment->getArticleId()}/{$reviewAssignment->getReviewId()}">{if $reviewAssignment->getDateDue()}{$reviewAssignment->getDateDue()|date_format:$dateFormatShort}{else}&mdash;{/if}</a></td>
					<td>
						{if $reviewAssignment->getDateAcknowledged()}
							{$reviewAssignment->getDateAcknowledged()|date_format:$dateFormatShort}
						{elseif $reviewAssignment->getDateCompleted()}
							{icon name="mail" url="`$requestPageUrl`/thankReviewer?reviewId=`$reviewAssignment->getReviewId()`&articleId=`$submission->getArticleId()`"}
						{else}
							{icon name="mail" disabled="disabled" url="`$requestPageUrl`/thankReviewer?reviewId=`$reviewAssignment->getReviewId()`&articleId=`$submission->getArticleId()`"}
						{/if}
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
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
	<tr valign="top">
		<td class="label">{translate key="reviewer.article.reviewerComments"}</td>
		<td>
			{if $reviewAssignment->getMostRecentPeerReviewComment()}
				{assign var="comment" value=$reviewAssignment->getMostRecentPeerReviewComment()}
				<a href="javascript:openComments('{$requestPageUrl}/viewPeerReviewComments/{$submission->getArticleId()}/{$reviewAssignment->getReviewId()}#{$comment->getCommentId()}');" class="icon">{icon name="comment"}</a> {$comment->getDatePosted()|date_format:$dateFormatShort}
			{else}
				<a href="javascript:openComments('{$requestPageUrl}/viewPeerReviewComments/{$submission->getArticleId()}/{$reviewAssignment->getReviewId()}');" class="icon">{icon name="comment"}</a>
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="reviewer.article.uploadedFile"}</td>
		<td>
			<table width="100%" class="data">
				{foreach from=$reviewAssignment->getReviewerFileRevisions() item=reviewerFile key=key}
				<tr valign="top">
					<td valign="middle">
						<form name="authorView{$reviewAssignment->getReviewId()}" method="post" action="{$requestPageUrl}/makeReviewerFileViewable">
							<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$reviewerFile->getFileId()}/{$reviewerFile->getRevision()}" class="file">{$reviewerFile->getFileName()}</a> {$reviewerFile->getDateModified()|date_format:$dateFormatShort}
							<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}" />
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
							<input type="hidden" name="fileId" value="{$reviewerFile->getFileId()}" />
							<input type="hidden" name="revision" value="{$reviewerFile->getRevision()}" />
							{translate key="editor.article.showAuthor"} <input type="checkbox" name="viewable" value="1"{if $reviewerFile->getViewable()} checked="checked"{/if} />
							<input type="submit" value="{translate key="common.record"}" class="button" />
						</form>
					</td>
				</tr>
				{foreachelse}
				<tr valign="top">
					<td>{translate key="common.none"}</td>
				</tr>
				{/foreach}
			</table>
		</td>
	</tr>
	{if $rateReviewerOnTimeliness or $rateReviewerOnQuality}
	<tr valign="top">
		<td class="label">{translate key="editor.article.rateReviewer"}</td>
		<td>
		<form method="post" action="{$requestPageUrl}/rateReviewer">
			<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}" />
			<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
			{if $rateReviewerOnTimeliness}
				{translate key="editor.article.timeliness"}&nbsp;
				<select name="timeliness" size="1" class="selectMenu"{if not $reviewAssignment->getRecommendation()} disabled="disabled"{/if}>
					{html_options_translate options=$reviewerRatingOptions selected=$reviewAssignment->getTimeliness()}
				</select>&nbsp;&nbsp;&nbsp;&nbsp;
			{/if}
			{if $rateReviewerOnQuality}
				{translate key="editor.article.quality"}&nbsp;
				<select name="quality" size="1" class="selectMenu"{if not $reviewAssignment->getRecommendation()} disabled="disabled"{/if}>
					{html_options_translate options=$reviewerRatingOptions selected=$reviewAssignment->getQuality()}
				</select>&nbsp;&nbsp;&nbsp;&nbsp;
			{/if}
			{if $reviewAssignment->getDateRated()}
				{$reviewAssignment->getDateRated()|date_format:$dateFormatShort}
			{/if}
			<input type="submit" value="{translate key="common.record"}"{if not $reviewAssignment->getRecommendation()} disabled="disabled"{/if} class="button" />
		</form>
		</td>
	</tr>
	{/if}
	</table>
{/if}
{/foreach}
