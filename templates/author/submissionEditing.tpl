{**
 * submissionEditing.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the status of an author's submission.
 *
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

<div class="formSectionTitle">{translate key="submission.copyedit"}</div>
<div class="formSection">
<table class="plain" width="100%">
<tr>
	<td width="5%"></td>
	<td width="25%"></td>
	<td width="40%"></td>
	<td class="label" width="15%">{translate key="submission.request"}</td>
	<td class="label" width="15%">{translate key="submission.complete"}</td>
</tr>
<tr>
	<td>1.</td>
	<td>{translate key="submission.initialCopyedit"}</td>
	<td></td>
	<td>{$article->getCopyeditorDateNotified()|date_format:$dateFormatShort}</td>
	<td>{$article->getCopyeditorDateCompleted()|date_format:$dateFormatShort}</td>
</tr>
<tr>
	<td>2.</td>
	<td>{translate key="submission.editorAuthorReview"}</td>
	<td align="right">
		{if not $article->getCopyeditorDateCompleted()}
			<form method="post" action="{$pageUrl}/author/completeAuthorCopyedit">
				<input type="hidden" name="articleId" value="{$article->getArticleId()}">
				<input type="submit" value="{translate key="author.article.complete"}">
			</form>
		{/if}
	</td>
	<td>{$article->getCopyeditorDateAuthorNotified()|date_format:$dateFormatShort}</td>
	<td>{$article->getCopyeditorDateAuthorCompleted()|date_format:$dateFormatShort}</td>
</tr>
<tr>
	<td>3.</td>
	<td>{translate key="submission.finalCopyedit"}</td>
	<td></td>
	<td></td>
	<td></td>
</tr>
</table>
</div>
{include file="common/footer.tpl"}
