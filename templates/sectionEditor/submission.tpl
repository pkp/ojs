{**
 * submission.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the details of a submission.
 *
 * FIXME: Editor decision values need to be localized.
 * DO: Reviewer comments need to be implemented.
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submission"}
{include file="common/header.tpl"}

<ul id="tabnav">
	<li><a href="{$pageUrl}/sectionEditor/submission/{$submission->getArticleId()}" class="active">{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$pageUrl}/sectionEditor/submissionEditing/{$submission->getArticleId()}">{translate key="submission.submissionEditing"}</a></li>
</ul>
<ul id="subnav">
	<li><a href="#peerReview">{translate key="submission.peerReview"}</a></li>
	<li><a href="#editorReview">{translate key="submission.editorReview"}</a></li>
</ul>

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
	<td>[<a href="{$pageUrl}/sectionEditor/viewMetadata/{$submission->getArticleId()}">{translate key="article.metadata"}</a>]</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.section"}:</td>
	<td>
		<form method="post" action="{$pageUrl}/sectionEditor/changeSection">
		<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
		<select name="sectionId">
		{foreach from=$sections item=section}
			<option value="{$section->getSectionId()}" {if $section->getTitle() eq $submission->getSectionTitle()}selected="selected"{/if}>{$section->getTitle()}</option>
		{/foreach}
		</select>
		<input type="submit" value="{translate key="editor.article.changeSection"}">
		</form>
	</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.file"}:</td>
	<td>
		{if $submissionFile}
			<a href="{$pageUrl}/sectionEditor/downloadFile?fileId={$submissionFile->getFileId()}">{$submissionFile->getFileName()}</a> {$submissionFile->getDateModified()|date_format:$dateFormatShort}</td>
		{/if}
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.suppFiles"}:</td>
	<td>
		{foreach from=$suppFiles item=suppFile}
			<div><a href="{$pageUrl}/author/downloadFile/{$suppFile->getFileId()}">{$suppFile->getTitle()}</a></div>
		{foreachelse}
			<div>{translate key="common.none"}</div>
		{/foreach}
	</td>
	<td align="right">[<a href="{$pageUrl}/sectionEditor/addSuppFile/{$submission->getArticleId()}">{translate key="submission.addSuppFile"}</a>]</td>
</tr>
</table>
</div>

<br />
<br />

<a name="peerReview"></a>
<div class="formSectionTitle">{translate key="submission.peerReview"}</div>
<div class="formSection">
<table class="plain" width="100%">
{assign var="start" value="A"|ord} 
{assign var="numReviewAssignments" value=$reviewAssignments|@count} 
{foreach from=$reviewAssignments item=reviewAssignment key=key}
<tr class="{cycle values="row,rowAlt"}">
	<td>
		<table class="plainFormat" width="100%">
			<tr>
				<td valign="top" width="5%">{$key+$start|chr}.</td>
				<td valign="top" width="35%"><a href="#">{$reviewAssignment->getReviewerFullName()}</a></td>
				<td valign="top" align="center" width="15%">
					<strong>{translate key="submission.request"}</strong><br />
					{if $reviewAssignment->getDateNotified()}{$reviewAssignment->getDateNotified()|date_format:$dateFormatShort}{else}-{/if}
				</td>
				<td valign="top" align="center" width="15%">
					<strong>{translate key="submission.acceptance"}</strong><br />
					{if $reviewAssignment->getDateConfirmed()}{$reviewAssignment->getDateConfirmed()|date_format:$dateFormatShort}{else}-{/if}
				</td>
				<td valign="top" align="center" width="15%">
					<strong>{translate key="submission.due"}</strong><br />
					{if $reviewAssignment->getDateDue()}
						<a href="{$pageUrl}/editor/setDueDate/{$reviewAssignment->getArticleId()}/{$reviewAssignment->getReviewId()}">{$reviewAssignment->getDateDue()|date_format:$dateFormatShort}</a>
					{else}
						<a href="{$pageUrl}/editor/setDueDate/{$reviewAssignment->getArticleId()}/{$reviewAssignment->getReviewId()}">{translate key="editor.article.setDueDate"}</a>
					{/if}
				</td>
				<td valign="top" align="center" width="15%">
					<strong>{translate key="submission.thank"}</strong><br />
					{if $reviewAssignment->getDateAcknowledged()}{$reviewAssignment->getDateAcknowledged()|date_format:$dateFormatShort}{else}-{/if}
				</td>
			</tr>
			<tr>
				<td></td>
				<td colspan="4">
					<form method="post" action="{$pageUrl}/sectionEditor/notifyReviewer">
						<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
						<input type="submit" value="{translate key="editor.article.notifyReviewer"}">
					</form>
				</td>
				<td align="center">
					{if $reviewAssignment->getDateCompleted() and not $reviewAssignment->getDateAcknowledged()}
						<form method="post" action="{$pageUrl}/sectionEditor/thankReviewer">
							<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="editor.article.thank"}">
						</form>
					{/if}
				</td>
			</tr>
			<tr>
				<td></td>
				<td colspan="5">
					{translate key="reviewer.article.recommendation"}: {if $reviewAssignment->getRecommendation()}{$reviewAssignment->getRecommendation()}{else}{translate key="common.none"}{/if}
					| <a href="#">{translate key="reviewer.article.reviewerComments"}</a> 2004/12/12 (new)
				</td>
			</tr>
			<tr>
				<td></td>
				<td colspan="5">
					<form name="authorView{$reviewAssignment->getReviewId()}" method="post" action="{$pageUrl}/sectionEditor/makeReviewFileViewable">
						<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
						{translate key="reviewer.article.reviewersAnnotatedVersion"}:
						{if $reviewAssignment->getReviewFileId()}
							{assign var="reviewFile" value=$reviewAssignment->getReviewFile()}
							<a href="{$pageUrl}/sectionEditor/downloadFile/{$reviewFile->getFileId()}">{$reviewFile->getFileName()}</a>
						{else}
							{translate key="common.none"}
						{/if}
						| <input type="checkbox" name="viewable" value="1" onclick="document.authorView{$reviewAssignment->getReviewId()}.submit()" {if $reviewAssignment->getReviewFileViewable()}checked="checked"{/if}> {translate key="editor.article.authorCanView"}
					</form>
				</td>
			</tr>
			<tr>
				<td></td>
				<td colspan="5">
					<form method="post" action="{$pageUrl}/sectionEditor/rateReviewer">
						<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
						{translate key="editor.article.reviewerRating"}:
						{translate key="editor.article.timeliness"}
						<select name="timeliness">
							<option value="5" {if $reviewAssignment->getTimeliness() eq 5}selected="selected"{/if}>5 High</option>
							<option value="4" {if $reviewAssignment->getTimeliness() eq 4}selected="selected"{/if}>4</option>
							<option value="3" {if $reviewAssignment->getTimeliness() eq 3}selected="selected"{/if}>3</option>
							<option value="2" {if $reviewAssignment->getTimeliness() eq 2}selected="selected"{/if}>2</option>
							<option value="1" {if $reviewAssignment->getTimeliness() eq 1}selected="selected"{/if}>1 Low</option>
						</select>
						{translate key="editor.article.quality"}
						<select name="quality">
							<option value="5" {if $reviewAssignment->getQuality() eq 5}selected="selected"{/if}>5 High</option>
							<option value="4" {if $reviewAssignment->getQuality() eq 4}selected="selected"{/if}>4</option>
							<option value="3" {if $reviewAssignment->getQuality() eq 3}selected="selected"{/if}>3</option>
							<option value="2" {if $reviewAssignment->getQuality() eq 2}selected="selected"{/if}>2</option>
							<option value="1" {if $reviewAssignment->getQuality() eq 1}selected="selected"{/if}>1 Low</option>
						</select>
						<input type="submit" value="{translate key="common.save}">
					</form>
				</td>
			</tr>
		</table>
	</td>
</tr>
{/foreach}
{section name="selectReviewer" start=0 loop=$numSelectReviewers}
<tr class="{cycle values="row,rowAlt"}">
	<td>
		<table class="plainFormat" width="100%">
			<tr>
				<td valign="top" width="5%">{$smarty.section.selectReviewer.index+$numReviewAssignments+$start|chr}.</td>
				<td valign="top" width="35%">{translate key="editor.article.notAssigned"}</td>
				<td valign="top" align="center" width="15%"><strong>{translate key="submission.request"}</strong><br />-</td>
				<td valign="top" align="center" width="15%"><strong>{translate key="submission.acceptance"}</strong><br />-</td>
				<td valign="top" align="center" width="15%"><strong>{translate key="submission.due"}</strong><br />-</td>
				<td valign="top" align="center" width="15%"><strong>{translate key="submission.thank"}</strong><br />-</td>
			</tr>
			<tr>
				<td></td>
				<td colspan="4">
					<form method="post" action="{$pageUrl}/sectionEditor/notifyReviewer">
						<input type="submit" value="{translate key="editor.article.notifyReviewer"}" disabled="disabled">
					</form>
				</td>
				<td align="center">
					<form method="post" action="{$pageUrl}/sectionEditor/thankReviewer">
						<input type="submit" value="{translate key="editor.article.thank"}" disabled="disabled">
					</form>
				</td>
			</tr>
			<tr>
				<td></td>
				<td colspan="5">
					{translate key="reviewer.article.recommendation"}: {translate key="common.none"}
					| {translate key="reviewer.article.reviewerComments"}
				</td>
			</tr>
			<tr>
				<td></td>
				<td colspan="5">
					<form method="post" action"">
						{translate key="reviewer.article.reviewersAnnotatedVersion"}: {translate key="common.none"}
						| <input type="checkbox" name="authorView" value="1" disabled="disabled"> {translate key="editor.article.authorCanView"}
					</form>
				</td>
			</tr>
			<tr>
				<td></td>
				<td colspan="5">
					<form method="post" action"">
						{translate key="editor.article.reviewerRating"}:
						{translate key="editor.article.timeliness"}
						<select name="timeliness" disabled="disabled">
							<option value="5">5 High</option>
						</select>
						{translate key="editor.article.quality"}
						<select name="quality" disabled="disabled">
							<option value="5">5 High</option>
						</select>
						<input type="submit" value="{translate key="common.save}" disabled="disabled">
					</form>
				</td>
			</tr>
		</table>
	</td>
</tr>
{/section}
<tr class="{cycle values="row,rowAlt"}">
	<td width="100%">
		<table class="plainFormat" width="100%">
			<tr>
				<td class="label" valign="top">{translate key="editor.article.options"}:</td>
				<td>
					<form method="post" action="{$pageUrl}/sectionEditor/selectReviewer/{$submission->getArticleId()}">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
						<input type="submit" value="{translate key="editor.article.addReviewer"}">
					</form>
				</td>
				<td align="right">
					<div>
						{translate key="editor.article.clearReviewer"}:
						{foreach from=$reviewAssignments item=reviewAssignment key=key}
							<a href="{$pageUrl}/sectionEditor/clearReviewer/{$submission->getArticleId()}/{$reviewAssignment->getReviewId()}">{$key+$start|chr}</a></span>
						{/foreach}
					</div>
					<div>
						{translate key="editor.article.editorToEnter"}:
						{foreach from=$reviewAssignments item=reviewAssignment key=key}
							<a href="{$pageUrl}/sectionEditor/enterForReviewer/{$submission->getArticleId()}/{$reviewAssignment->getReviewId()}">{$key+$start|chr}</a>
						{/foreach}
					</div>
				</td>
			</tr>
		</table>
	</td>
</tr>
</table>
</div>

<br />
<br />

<a name="editorReview"></a>
<div class="formSectionTitle">{translate key="submission.editorReview"}</div>
<div class="formSection">
<table class="form">
<tr>
	<td class="formLabel">{translate key="user.role.editor"}:</td>
	<td colspan="2">
		{if $editor}
			{$editor->getFullName()}
		{else}
			{translate key="editor.article.noEditorSelected"}
		{/if}
	</td>
</tr>
<tr>
	<td></td>
	<td colspan="2">[<a href="">{translate key="submission.editorAuthorComments"}</a>]</td>
</tr>
<tr>
	<td class="formLabel">{translate key="editor.article.decision"}:</td>
	<td colspan="2">
	{if $submission->getRecommendation()}
		{if $submission->getRecommendation() eq 2}
			Accept
		{else if $submission->getRecommendation() eq 3}
			Decline
		{/if}
	{else}
		<form method="post" action="{$pageUrl}/editor/recordRecommendation}">
		<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
		<select name="recommendation">
			<option value="1" selected="selected">Pending</option>
			<option value="2">Accept</option>
			<option value="3">Decline</option>
		</select>
		<input type="submit" name="submit" value="{translate key="editor.article.recordDecision"}">
		</form>		
	{/if}
	</td>
</tr>
</table>
<table class="plain" width="100%">
<tr>
	<td valign="top">
		{translate key="submission.postReviewVersion"}:
		{if $postReviewFile}
			<a href="{$pageUrl}/sectionEditor/downloadFile/{$postReviewFile->getFileId()}">{$postReviewFile->getFileName()}</a> {$postReviewFile->getDateModified()|date_format:$dateFormatShort}
		{else}
			{translate key="common.required"}
		{/if}
	</td>
	<td>
		<form method="post" action="{$pageUrl}/sectionEditor/uploadPostReviewArticle" enctype="multipart/form-data">
			<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
			<input type="file" name="upload">
			<input type="submit" name="submit" value="{translate key="common.upload"}">
		</form>
	</td>
</tr>
<tr>
	<td valign="top">
		{translate key="submission.authorsRevisedVersion"}:
		{if $revisedFile}
			<a href="{$pageUrl}/sectionEditor/downloadFile/{$revisedFile->getFileId()}">{$revisedFile->getFileName()}</a> {$revisedFile->getDateModified()|date_format:$dateFormatShort}
		{else}
			{translate key="common.none"}
		{/if}
	</td>
	<td>
		{if $revisedFile}
			<form method="post" action="">
				<input type="submit" value="{translate key="editor.article.reviewersSeeAuthorsVersion"}">
			</form>
		{/if}
	</td>
</tr>
</table>
</div>
{include file="common/footer.tpl"}
