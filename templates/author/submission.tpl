{**
 * submission.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the status of an author's submission.
 *
 * Note: Still missing most of the data to display on this screen.
 *
 * $Id$
 *}

{assign var="pageTitle" value="author.submissions"}
{include file="common/header.tpl"}

<div class="formSectionTitle">Submission</div>
<div class="formSection">
<table class="form" width="100%">
<tr>
	<td class="formLabel">Title:</td>
	<td>{$article->getTitle()}</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">Author(s):</td>
	<td>
		{foreach from=$article->getAuthors() item=author}
			<div>{$author->getFullName()}</div>
		{/foreach}
	</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">Indexing Information:</td>
	<td>[<a href="">Metadata</a>]</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">Section:</td>
	<td>{$article->getSectionTitle()}</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">File:</td>
	<td>
		{if $submissionFile}
			<a href="{$pageUrl}/author/downloadFile?fileId={$submissionFile->getFileId()}">{$submissionFile->getFileName()}</a> {$submissionFile->getDateModified()}</td>
		{/if}
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">Supplementary Files:</td>
	<td>
		{foreach from=$suppFiles item=suppFile}
			<div><a href="{$pageUrl}/author/downloadFile?fileId={$submissionFile->getFileId()}">{$suppFile->getTitle()}</a></div>
		{foreachelse}
			<div>None</div>
		{/foreach}
	</td>
	<td align="right">[<a href="{$pageUrl}/author/submitSuppFile?articleId={$article->getArticleId()}">Add Supplementary File</a>]</td>
</tr>
</table>
</div>

<br />
<br />

<div class="formSectionTitle">Peer Review</div>
<div class="formSection">
<table class="plain" width="100%">
<tr>
	<td class="label" width="5%">&nbsp;</td>
	<td class="label" width="50%">&nbsp;</td>
	<td class="label" width="15%">Request</td>
	<td class="label" width="15%">Accept</td>
	<td class="label" width="15%">Due</td>
</tr>
{assign var="start" value="A"|ord} 
{assign var="numReviewAssignments" value=$reviewAssignments|@count} 
{foreach from=$article->getReviewAssignments() item=reviewAssignment key=key}
	<tr class="{cycle values="row,rowAlt"}">
		<td width="5%" valign="top">{$key+$start|chr}.</td>
		<td class="formLabel" width="50%">Reviewer</td>
		<td class="formLabel" width="15%">{$reviewAssignment->getDateNotified()}</td>
		<td class="formLabel" width="15%">{$reviewAssignment->getDateConfirmed()}</td>
		<td class="formLabel" width="15%">d/m/y</td>
	</tr>
{foreachelse}
	<tr>
		<td colspan="5" class="noResults">No Review Assignments</td>
	</tr>
{/foreach}
</table>
</div>

<br />
<br />

<div class="formSectionTitle">Editor Review</div>
<div class="formSection">
<table class="form">
<tr>
	<td class="formLabel">Editor:</td>
	<td>
		{if $editor}
			<a href="mailto:{$editor->getEmail()}">{$editor->getFullName()}</a>
		{/if}
	</td>
</tr>
<tr>
	<td></td>
	<td>[<a href="">Editor/Author Comments</a>]</td>
</tr>
<tr>
	<td colspan="2">Post-review version of file:
		{if strlen($postReviewFile) gt 0}
			{$postReviewFile->getFileName()}
		{else}
			None
		{/if}
	</td>
</tr>
<tr>
	<td></td>
	<td>
		
	</td>
</tr>
<tr>
	<td colspan="2">Author's revised version of file:</td>
</tr>
<form method="post" action="{$pageUrl}/author/uploadRevisedArticle" enctype="multipart/form-data">
<input type="hidden" name="articleId" value="{$article->getArticleId()}" />
<tr>
	<td></td>
	<td>
		<input type="file" name="upload">
		<input type="submit" name="submit" value="Upload">
	</td>
</tr>
</form>
</table>
</div>
{include file="common/footer.tpl"}
