{**
 * submission.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the details of a submission.
 *
 * FIXME: Editor decision values need to be localized.
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
	<td>[<a href="{$pageUrl}/sectionEditor/viewMetadata/{$submission->getArticleId()}">{translate key="article.metadata"}</a>]</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.section"}:</td>
	<td>
		<form method="post" action="{$pageUrl}/editor/changeSection">
		<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
		<select name="sectionId">
		{foreach from=$sections item=section}
			<option value="{$section->getSectionId()}" {if $section->getTitle() eq $submission->getSectionTitle()}selected="selected"{/if}>{$section->getTitle()}</option>
		{/foreach}
		</select>
		<input type="submit" value="{translate key="submission.changeSection"}">
		</form>
	</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.file"}:</td>
	<td>
		{foreach from=$files item=file}
			<div>{$file->getFileName()}</div>
		{foreachelse}
			<div>{translate key="common.none"}</div>
		{/foreach}
	</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.suppFiles"}:</td>
	<td>
		{foreach from=$suppFiles item=suppFile}
			<div>{$suppFile->getTitle()}</div>
		{foreachelse}
			<div>{translate key="common.none"}</div>
		{/foreach}
	</td>
	<td align="right">[<a href="">{translate key="submission.addSuppFile"}</a>]</td>
</tr>
</table>
</div>

<br />
<br />

<div class="formSectionTitle">{translate key="submission.peerReview"}</div>
<div class="formSection">
<table class="plain" width="100%">
<tr>
	<td width="5%">&nbsp;</td>
	<td width="30%">&nbsp;</td>
	<td width="20%">&nbsp;</td>
	<td width="10%" class="label">{translate key="submission.request"}</td>
	<td width="10%" class="label">{translate key="submission.accept"}</td>
	<td width="10%" class="label">{translate key="submission.due"}</td>
	<td width="10%" class="label">{translate key="submission.thank"}</td>
	<td width="5%" class="label"></td>
</tr>
{assign var="start" value="A"|ord} 
{assign var="numReviewAssignments" value=$reviewAssignments|@count} 
{foreach from=$reviewAssignments item=reviewAssignment key=key}
	<tr class="{cycle values="row,rowAlt"}">
		<td width="5%" valign="top">{$key+$start|chr}.</td>
		<td width="30%" valign="top">
			<div>{$reviewAssignment->getReviewerFullName()}</div>
			<div>[<a href="">{translate key="submission.reviewerComments"}</a>]</div>
		</td>
		<td width="20%" valign="top" align="right">
			<table class="plain" width="100%">
				<tr>
					{if not $reviewAssignment->getDateNotified()}
						<td align="right">
							<form method="post" action="{$pageUrl}/sectionEditor/notifyReviewer">
							<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="submission.notifyReviewer"}">
							</form>
						</td>
					{else}
						<td align="right">
							<form method="post" action="{$pageUrl}/sectionEditor/replaceReviewer/{$submission->getArticleId()}/{$reviewAssignment->getReviewId()}">
							<input type="submit" value="{translate key="submission.replace"}">
							</form>
						</td>
						<td align="right">
							<form method="post" action="{$pageUrl}/sectionEditor/remindReviewer">
							<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="submission.remindReviewer"}">
							</form>
						</td>
					{/if}
				</tr>
			</table>
		</td>
		<td width="10%" valign="top">{$reviewAssignment->getDateNotified()|date_format:$dateFormatShort}</td>
		<td width="10%" valign="top">{$reviewAssignment->getDateConfirmed()|date_format:$dateFormatShort}</td>
		<td width="10%" valign="top">
			{if $reviewAssignment->getDateDue()}
				<a href="{$pageUrl}/editor/setDueDate/{$reviewAssignment->getArticleId()}/{$reviewAssignment->getReviewId()}">{$reviewAssignment->getDateDue()|date_format:$dateFormatShort}</a>
			{else}
				<a href="{$pageUrl}/editor/setDueDate/{$reviewAssignment->getArticleId()}/{$reviewAssignment->getReviewId()}">{translate key="submission.setDueDate"}</a>
			{/if}
		</td>
		<td width="10%" valign="top">{$reviewAssignment->getDateAcknowledged()|date_format:$dateFormatShort}</td>
		<td width="5%" valign="top"><a href="{$pageUrl}/editor/clearReviewer/{$reviewAssignment->getArticleId()}/{$reviewAssignment->getReviewId()}">{translate key="submission.clearReviewer"}</a></td>
	</tr>
{/foreach}
{section name="selectReviewer" start=0 loop=$numSelectReviewers}
	<tr class="{cycle values="row,rowAlt"}">
		<td width="5%">{$smarty.section.selectReviewer.index+$numReviewAssignments+$start|chr}.</td>
		<td width="30%"><a href="{$pageUrl}/editor/selectReviewer/{$submission->getArticleId()}">{translate key="submission.selectReviewer"}</a></td>
		<td width="20%"></td>
		<td width="10%">d/m/y</td>
		<td width="10%">d/m/y</td>
		<td width="10%">d/m/y</td>
		<td width="10%">d/m/y</td>
		<td width="5%"></td>
	</tr>
{/section}
</table>
</div>

<br />
<br />

<div class="formSectionTitle">{translate key="submission.editorReview"}</div>
<div class="formSection">
<table class="form">
<tr>
	<td class="formLabel">{translate key="user.role.editor"}:</td>
	<td colspan="2">
		{if $editor}
			{$editor->getFullName()}
		{else}
			{translate key="submission.noEditorSelected"}
		{/if}
	</td>
</tr>
<tr>
	<td></td>
	<td colspan="2">[<a href="">{translate key="submission.editorAuthorComments"}</a>]</td>
</tr>
<tr>
	<td class="formLabel">{translate key="submission.editorDecision"}:</td>
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
		<input type="submit" name="submit" value="{translate key="submission.recordDecision"}">
		</form>		
	{/if}
	</td>
</tr>
<tr>
	<td colspan="2">{translate key="submission.postReviewVersion"}:
		{if strlen($postReviewFile) gt 0}
			{$postReviewFile->getFileName()}
		{else}
			({translate key="common.required"})
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
	<td colspan="2">{translate key="submission.authorsRevisedVersion"}: {translate key="common.none"}</td>
</tr>
</table>
</div>
{include file="common/footer.tpl"}
