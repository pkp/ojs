{**
 * submission.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the status of an author's submission.
 *
 *
 * $Id$
 *}

{assign var="pageTitle" value="author.submissions"}
{include file="common/header.tpl"}

<div class="formSectionTitle">{translate key="submission.submission"}</div>
<div class="formSection">
<table class="form" width="100%">
<tr>
	<td class="formLabel">{translate key="article.title"}:</td>
	<td>{$article->getTitle()}</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.authors"}:</td>
	<td>
		{foreach from=$article->getAuthors() item=author}
			<div>{$author->getFullName()}</div>
		{/foreach}
	</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.indexingInformation"}:</td>
	<td>[<a href="{$pageUrl}/author/viewMetadata/{$article->getArticleId()}">{translate key="article.metadata"}</a>]</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.section"}:</td>
	<td>{$article->getSectionTitle()}</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.file"}:</td>
	<td>
		{if $submissionFile}
			<a href="{$pageUrl}/author/downloadFile?fileId={$submissionFile->getFileId()}">{$submissionFile->getFileName()}</a> {$submissionFile->getDateModified()|date_format:$dateFormatShort}</td>
		{/if}
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.suppFiles"}:</td>
	<td>
		{foreach from=$suppFiles item=suppFile}
			<div><a href="{$pageUrl}/author/downloadFile?fileId={$suppFile->getFileId()}">{$suppFile->getTitle()}</a></div>
		{foreachelse}
			<div>{translate key="common.none"}</div>
		{/foreach}
	</td>
	<td align="right">[<a href="{$pageUrl}/author/submitSuppFile?articleId={$article->getArticleId()}">{translate key="submission.addSuppFile"}</a>]</td>
</tr>
</table>
</div>

<br />
<br />

<div class="formSectionTitle">{translate key="submission.peerReview"}</div>
<div class="formSection">
<table class="plain" width="100%">
<tr>
	<td class="label" width="5%">&nbsp;</td>
	<td class="label" width="50%">&nbsp;</td>
	<td class="label" width="15%">{translate key="submission.request"}</td>
	<td class="label" width="15%">{translate key="submission.accept"}</td>
	<td class="label" width="15%">{translate key="submission.due"}</td>
</tr>
{assign var="start" value="A"|ord} 
{assign var="numReviewAssignments" value=$reviewAssignments|@count} 
{foreach from=$article->getReviewAssignments() item=reviewAssignment key=key}
	<tr class="{cycle values="row,rowAlt"}">
		<td width="5%" valign="top">{$key+$start|chr}.</td>
		<td class="formLabel" width="50%">{translate key="user.role.reviewer"}</td>
		<td class="formLabel" width="15%">{$reviewAssignment->getDateNotified()|date_format:$dateFormatShort}</td>
		<td class="formLabel" width="15%">{$reviewAssignment->getDateConfirmed()|date_format:$dateFormatShort}</td>
		<td class="formLabel" width="15%">{$reviewAssignment->getDateDue()|date_format:$dateFormatShort}</td>
	</tr>
{foreachelse}
	<tr>
		<td colspan="5" class="noResults">{translate key="submission.noReviewAssignments"}</td>
	</tr>
{/foreach}
</table>
</div>

<br />
<br />

<div class="formSectionTitle">{translate key="submission.editorReview"}</div>
<div class="formSection">
<table class="form">
<tr>
	<td class="formLabel">{translate key="user.role.editor"}:</td>
	<td>
		{if $editor}
			<a href="mailto:{$editor->getEmail()}">{$editor->getFullName()}</a>
		{/if}
	</td>
</tr>
<tr>
	<td></td>
	<td>[<a href="">{translate key="submission.editorAuthorComments"}</a>]</td>
</tr>
<tr>
	<td colspan="2">{translate key="submission.postReviewVersion"}:
		{if strlen($postReviewFile) gt 0}
			{$postReviewFile->getFileName()}
		{else}
			{translate key="common.none"}
		{/if}
	</td>
</tr>
<tr>
	<td></td>
	<td>
		
	</td>
</tr>
<tr>
	<td colspan="2">{translate key="submission.authorsRevisedVersion"}:</td>
</tr>
<form method="post" action="{$pageUrl}/author/uploadRevisedArticle" enctype="multipart/form-data">
<input type="hidden" name="articleId" value="{$article->getArticleId()}" />
<tr>
	<td></td>
	<td>
		<input type="file" name="upload">
		<input type="submit" name="submit" value="{translate key="common.upload"}">
	</td>
</tr>
</form>
</table>
</div>
{include file="common/footer.tpl"}
