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
	<td colspan="2">
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
	<td colspan="2">
		<form method="post" action="{$requestPageUrl}/uploadReviewVersion" enctype="multipart/form-data">
			{translate key="editor.article.uploadReviewVersion"}
			<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
			<input type="file" name="upload" class="uploadField" />
			<input type="submit" name="submit" value="{translate key="common.upload"}" class="button" />
		</form>
	</td>
</tr>
<tr valign="top">
	<td class="label" width="22%">{translate key="submission.reviewVersion"}</td>
	{if $reviewFile}
		<td width="15%" class="value">
			<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$reviewFile->getFileId()}/{$reviewFile->getRevision()}" class="file">{$reviewFile->getFileName()}</a>&nbsp;&nbsp;
			{$reviewFile->getDateModified()|date_format:$dateFormatShort}
		</td>
	{else}
		<td width="80%" class="nodata">{translate key="common.none"}</td>
	{/if}
</tr>
{foreach from=$suppFiles item=suppFile}
	<form method="post" action="{$requestPageUrl}/setSuppFileVisibility">
	<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
	<input type="hidden" name="fileId" value="{$suppFile->getSuppFileId()}" />

	<tr valign="top">
		{if !$notFirstSuppFile}
			<td class="label" rowspan="{$suppFiles|@count}">{translate key="article.suppFilesAbbrev"}</td>
			{assign var=notFirstSuppFile value=1}
		{/if}
		<td width="15%" class="value">
			<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$suppFile->getFileId()}/{$suppFile->getRevision()}" class="file">{$suppFile->getFileName()}</a>&nbsp;&nbsp;
			{$suppFile->getDateModified()|date_format:$dateFormatShort}
			<nobr>
				<label for="show">{translate key="editor.article.showSuppFile"}</label>
				<input type="checkbox" name="show" id="show" value="1"{if $suppFile->getShowReviewers()==1} checked="checked"{/if}/>
				<input type="submit" name="submit" value="{translate key="common.record"}" class="button" />
			</nobr>
		</td>
	</tr>
	</form>
{foreachelse}
	<tr valign="top">
		<td class="label">{translate key="article.suppFilesAbbrev"}</td>
		<td class="nodata">{translate key="common.none"}</td>
	</tr>
{/foreach}
</table>

<div class="separator"></div>

<a name="peerReview"></a>
<table class="data" width="100%">
	<tr>
		<td width="22%"><h3>{translate key="submission.peerReview"}</h3></td>
		<td width="14%"><h4>{translate key="submission.round" round=$round}</h4></td>
		<td width="64%">
			<a href="{$requestPageUrl}/selectReviewer/{$submission->getArticleId()}" class="action">{translate key="editor.article.selectReviewer"}</a>&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="{$requestPageUrl}/submissionRegrets/{$submission->getArticleId()}" class="action">{translate key="sectionEditor.regrets.link"}</a>
		</td>
	</tr>
</table>


{assign var="start" value="A"|ord} 
{foreach from=$reviewAssignments item=reviewAssignment key=reviewKey}
{assign var="reviewId" value=$reviewAssignment->getReviewId()}

{if not $reviewAssignment->getCancelled()}
	<div class="separator"></div>

	<table class="data" width="100%">
	<tr>
		<td width="22%"><h4>{translate key="user.role.reviewer"} {$reviewKey+$start|chr}</h4></td>
		<td width="33%"><h4>{$reviewAssignment->getReviewerFullName()}</h4></td>
		<td width="45%">
				{if not $reviewAssignment->getDateNotified()}
					<a href="{$requestPageUrl}/clearReview/{$submission->getArticleId()}/{$reviewAssignment->getReviewId()}" class="action">{translate key="editor.article.clearReview"}</a>
				{elseif not $reviewAssignment->getDateCompleted()}
					<a href="{$requestPageUrl}/cancelReview?articleId={$submission->getArticleId()}&reviewId={$reviewAssignment->getReviewId()}" class="action">{translate key="editor.article.cancelReview"}</a>
				{/if}
		</td>
	</tr>
	</table>

	<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="21%">{translate key="submission.schedule"}</td>
		<td width="79%">
			<table width="100%" class="info">
				<tr>
					<td class="heading" width="25%">{translate key="submission.request"}</td>
					<td class="heading" width="25%">{translate key="submission.underway"}</td>
					<td class="heading" width="25%">{translate key="submission.due"}</td>
					<td class="heading" width="25%">{translate key="submission.acknowledge"}</td>
				</tr>
				<tr valign="top">
					<td>
						{if $reviewAssignment->getDateNotified()}
							{$reviewAssignment->getDateNotified()|date_format:$dateFormatShort}
						{elseif $reviewAssignment->getReviewFileId()}
							{icon name="mail" url="`$requestPageUrl`/notifyReviewer?reviewId=`$reviewAssignment->getReviewId()`&articleId=`$submission->getArticleId()`"}
						{else}
							{icon name="mail" disabled="disabled" url="`$requestPageUrl`/notifyReviewer?reviewId=`$reviewAssignment->getReviewId()`&articleId=`$submission->getArticleId()`"}
						{/if}
					</td>
					<td>
						{if $reviewAssignment->getDateConfirmed()}
							{$reviewAssignment->getDateConfirmed()|date_format:$dateFormatShort}
						{else}
							&mdash;
						{/if}
					</td>
					<td>
						{if $reviewAssignment->getDeclined()}
							{translate key="sectionEditor.regrets"}
						{else}
							<a href="{$requestPageUrl}/setDueDate/{$reviewAssignment->getArticleId()}/{$reviewAssignment->getReviewId()}">{if $reviewAssignment->getDateDue()}{$reviewAssignment->getDateDue()|date_format:$dateFormatShort}{else}&mdash;{/if}</a>
						{/if}
					</td>
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

	{if $reviewAssignment->getDateConfirmed() && !$reviewAssignment->getDeclined()}
		<tr valign="top">
			<td class="label">{translate key="reviewer.article.recommendation"}</td>
			<td>
				{if $reviewAssignment->getRecommendation()}
					{assign var="recommendation" value=$reviewAssignment->getRecommendation()}
					{translate key=$reviewerRecommendationOptions.$recommendation}
					&nbsp;&nbsp;{$reviewAssignment->getDateCompleted()|date_format:$dateFormatShort}
				{else}
					{translate key="common.none"}&nbsp;&nbsp;&nbsp;&nbsp;
					<a href="{$requestPageUrl}/remindReviewer?articleId={$submission->getArticleId()}&reviewId={$reviewAssignment->getReviewId()}" class="action">{translate key="reviewer.article.sendReminder"}</a>
					{if $reviewAssignment->getDateReminded()}
						&nbsp;&nbsp;{$reviewAssignment->getDateReminded()|date_format:$dateFormatShort}
						{if $reviewAssignment->getReminderWasAutomatic()}
							&nbsp;&nbsp;{translate key="reviewer.article.automatic"}
						{/if}
					{/if}
				{/if}
			</td>
		</tr>
		<tr valign="top">
			<td class="label">{translate key="submission.review"}</td>
			<td>
				{if $reviewAssignment->getMostRecentPeerReviewComment()}
					{assign var="comment" value=$reviewAssignment->getMostRecentPeerReviewComment()}
					<a href="javascript:openComments('{$requestPageUrl}/viewPeerReviewComments/{$submission->getArticleId()}/{$reviewAssignment->getReviewId()}#{$comment->getCommentId()}');" class="action">{icon name="letter"}</a>&nbsp;&nbsp;{$comment->getDatePosted()|date_format:$dateFormatShort}
				{else}
					<a href="javascript:openComments('{$requestPageUrl}/viewPeerReviewComments/{$submission->getArticleId()}/{$reviewAssignment->getReviewId()}');" class="action">{icon name="letter"}</a>
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
								<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$reviewerFile->getFileId()}/{$reviewerFile->getRevision()}" class="file">{$reviewerFile->getFileName()}</a>&nbsp;&nbsp;{$reviewerFile->getDateModified()|date_format:$dateFormatShort}
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
	{/if}

	{if (!$reviewAssignment->getRecommendation() || !$reviewAssignment->getDateConfirmed()) && $reviewAssignment->getDateNotified()}
		<tr valign="top">
			<td class="label">{translate key="reviewer.article.editorToEnter"}</td>
			<td>
				{if !$reviewAssignment->getDateConfirmed() || $reviewAssignment->getDeclined()}
					<a href="{$requestPageUrl}/acceptReviewForReviewer/{$submission->getArticleId()}/{$reviewAssignment->getReviewId()}" class="action">{translate key="submission.acceptance"}</a>&nbsp;&nbsp;
				{/if}
				<a class="action" href="{$requestPageUrl}/enterReviewerRecommendation?articleId={$submission->getArticleId()}&reviewId={$reviewAssignment->getReviewId()}">{translate key="editor.article.recommendation"}</a>
				</form>
			</td>
		</tr>
	{/if}

	{if $reviewAssignment->getDateConfirmed() && !$reviewAssignment->getDeclined()}
		{if $rateReviewerOnQuality}
			<tr valign="top">
				<td class="label">{translate key="editor.article.rateReviewer"}</td>
				<td>
				<form method="post" action="{$requestPageUrl}/rateReviewer">
					<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}" />
					<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
					{translate key="editor.article.quality"}&nbsp;
					<select name="quality" size="1" class="selectMenu"{if not $reviewAssignment->getRecommendation()} disabled="disabled"{/if}>
						{html_options_translate options=$reviewerRatingOptions selected=$reviewAssignment->getQuality()}
					</select>&nbsp;&nbsp;
					<input type="submit" value="{translate key="common.record"}"{if not $reviewAssignment->getRecommendation()} disabled="disabled"{/if} class="button" />
					{if $reviewAssignment->getDateRated()}
						&nbsp;&nbsp;{$reviewAssignment->getDateRated()|date_format:$dateFormatShort}
					{/if}
				</form>
				</td>
			</tr>
		{/if}
	{/if}
	</table>
{/if}
{/foreach}
