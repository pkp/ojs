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
		<form method="post" action="{url op="designateReviewVersion"}">
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
		<form method="post" action="{url op="uploadReviewVersion"}" enctype="multipart/form-data">
			{translate key="editor.article.uploadReviewVersion"}
			<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
			<input type="file" name="upload" class="uploadField" />
			<input type="submit" name="submit" value="{translate key="common.upload"}" class="button" />
		</form>
	</td>
</tr>
<tr valign="top">
	<td class="label" width="20%">{translate key="submission.reviewVersion"}</td>
	{if $reviewFile}
		<td width="80%" class="value">
			<a href="{url op="downloadFile" path=$submission->getArticleId()|to_array:$reviewFile->getFileId():$reviewFile->getRevision()}" class="file">{$reviewFile->getFileName()|escape}</a>&nbsp;&nbsp;
			{$reviewFile->getDateModified()|date_format:$dateFormatShort}
		</td>
	{else}
		<td width="80%" class="nodata">{translate key="common.none"}</td>
	{/if}
</tr>
{foreach from=$suppFiles item=suppFile}
	<form method="post" action="{url op="setSuppFileVisibility"}">
	<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
	<input type="hidden" name="fileId" value="{$suppFile->getSuppFileId()}" />

	<tr valign="top">
		{if !$notFirstSuppFile}
			<td class="label" rowspan="{$suppFiles|@count}">{translate key="article.suppFilesAbbrev"}</td>
			{assign var=notFirstSuppFile value=1}
		{/if}
		<td width="80%" class="value nowrap">
			<a href="{url op="downloadFile" path=$submission->getArticleId()|to_array:$suppFile->getFileId():$suppFile->getRevision()}" class="file">{$suppFile->getFileName()|escape}</a>&nbsp;&nbsp;
			{$suppFile->getDateModified()|date_format:$dateFormatShort}
				<label for="show">{translate key="editor.article.showSuppFile"}</label>
				<input type="checkbox" name="show" id="show" value="1"{if $suppFile->getShowReviewers()==1} checked="checked"{/if}/>
				<input type="submit" name="submit" value="{translate key="common.record"}" class="button" />
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
		<td width="64%" class="nowrap">
			<a href="{url op="selectReviewer" path=$submission->getArticleId()}" class="action">{translate key="editor.article.selectReviewer"}</a>&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="{url op="submissionRegrets" path=$submission->getArticleId()}" class="action">{translate key="sectionEditor.regrets.link"}</a>
		</td>
	</tr>
</table>

<br />

{* Determine whether any review assignments are available to initiate
   so that we know whether or not to display the Initiate All Reviews button *}
{assign var=reviewAvailable value=0}
{foreach from=$reviewAssignments item=reviewAssignment}
	{if !$reviewAssignment->getCancelled() && $reviewAssignment->getReviewFileId()}
		{assign var=reviewAvailable value=1}
	{/if}
{/foreach}

{assign var="start" value="A"|ord}
{foreach from=$reviewAssignments item=reviewAssignment key=reviewKey}
{assign var="reviewId" value=$reviewAssignment->getReviewId()}

{if not $reviewAssignment->getCancelled()}
	{assign var="reviewIndex" value=$reviewIndexes[$reviewId]}
	<div class="separator"></div>

	<table class="data" width="100%">
	<tr>
		<td width="20%"><h4>{translate key="user.role.reviewer"} {$reviewIndex+$start|chr}</h4></td>
		<td width="34%"><h4>{$reviewAssignment->getReviewerFullName()|escape}</h4></td>
		<td width="46%">
				{if not $reviewAssignment->getDateNotified()}
					<a href="{url op="clearReview" path=$submission->getArticleId()|to_array:$reviewAssignment->getReviewId()}" class="action">{translate key="editor.article.clearReview"}</a>
				{elseif $reviewAssignment->getDeclined() or not $reviewAssignment->getDateCompleted()}
					<a href="{url op="cancelReview" articleId=$submission->getArticleId() reviewId=$reviewAssignment->getReviewId()}" class="action">{translate key="editor.article.cancelReview"}</a>
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
					<td class="heading" width="25%">{translate key="submission.underway"}</td>
					<td class="heading" width="25%">{translate key="submission.due"}</td>
					<td class="heading" width="25%">{translate key="submission.acknowledge"}</td>
				</tr>
				<tr valign="top">
					<td>
						{url|assign:"reviewUrl" op="notifyReviewer" reviewId=$reviewAssignment->getReviewId() articleId=$submission->getArticleId()}
						{if $reviewAssignment->getDateNotified()}
							{$reviewAssignment->getDateNotified()|date_format:$dateFormatShort}
							{if !$reviewAssignment->getDateCompleted()}
								{icon name="mail" url=$reviewUrl}
							{/if}
						{elseif $reviewAssignment->getReviewFileId()}
							{icon name="mail" url=$reviewUrl}
						{else}
							{icon name="mail" disabled="disabled" url=$reviewUrl}
							{assign var=needsReviewFileNote value=1}
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
							<a href="{url op="setDueDate" path=$reviewAssignment->getArticleId()|to_array:$reviewAssignment->getReviewId()}">{if $reviewAssignment->getDateDue()}{$reviewAssignment->getDateDue()|date_format:$dateFormatShort}{else}&mdash;{/if}</a>
						{/if}
					</td>
					<td>
						{url|assign:"thankUrl" op="thankReviewer" reviewId=$reviewAssignment->getReviewId() articleId=$submission->getArticleId()}
						{if $reviewAssignment->getDateAcknowledged()}
							{$reviewAssignment->getDateAcknowledged()|date_format:$dateFormatShort}
						{elseif $reviewAssignment->getDateCompleted()}
							{icon name="mail" url=$thankUrl}
						{else}
							{icon name="mail" disabled="disabled" url=$thankUrl}
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
					<a href="{url op="remindReviewer" articleId=$submission->getArticleId() reviewId=$reviewAssignment->getReviewId()}" class="action">{translate key="reviewer.article.sendReminder"}</a>
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
					<a href="javascript:openComments('{url op="viewPeerReviewComments" path=$submission->getArticleId()|to_array:$reviewAssignment->getReviewId() anchor=$comment->getCommentId()}');" class="icon">{icon name="letter"}</a>&nbsp;&nbsp;{$comment->getDatePosted()|date_format:$dateFormatShort}
				{else}
					<a href="javascript:openComments('{url op="viewPeerReviewComments" path=$submission->getArticleId()|to_array:$reviewAssignment->getReviewId()}');" class="icon">{icon name="letter"}</a>&nbsp;&nbsp;{translate key="submission.comments.noComments"}
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
							<form name="authorView{$reviewAssignment->getReviewId()}" method="post" action="{url op="makeReviewerFileViewable"}">
								<a href="{url op="downloadFile" path=$submission->getArticleId()|to_array:$reviewerFile->getFileId():$reviewerFile->getRevision()}" class="file">{$reviewerFile->getFileName()|escape}</a>&nbsp;&nbsp;{$reviewerFile->getDateModified()|date_format:$dateFormatShort}
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

	{if (!$reviewAssignment->getRecommendation() || !$reviewAssignment->getDateConfirmed()) && $reviewAssignment->getDateNotified() && !$reviewAssignment->getDeclined()}
		<tr valign="top">
			<td class="label">{translate key="reviewer.article.editorToEnter"}</td>
			<td>
				{if !$reviewAssignment->getDateConfirmed()}
					<a href="{url op="acceptReviewForReviewer" path=$submission->getArticleId()|to_array:$reviewAssignment->getReviewId()}" class="action">{translate key="submission.acceptance"}</a>&nbsp;&nbsp;
				{/if}
				<a class="action" href="{url op="enterReviewerRecommendation" articleId=$submission->getArticleId() reviewId=$reviewAssignment->getReviewId()}">{translate key="editor.article.recommendation"}</a>
				</form>
			</td>
		</tr>
	{/if}

	{if $reviewAssignment->getDateConfirmed() && !$reviewAssignment->getDeclined()}
		{if $rateReviewerOnQuality}
			<tr valign="top">
				<td class="label">{translate key="editor.article.rateReviewer"}</td>
				<td>
				<form method="post" action="{url op="rateReviewer"}">
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
	{if $needsReviewFileNote}
		<tr valign="top">
			<td>&nbsp;</td>
			<td>
				{translate key="submission.review.mustUploadFileForReview"}
			</td>
		</tr>
	{/if}
	</table>
{/if}
{/foreach}
