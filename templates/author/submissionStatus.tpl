{**
 * submissionStatus.tpl
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

{assign var="pageTitle" value="article.submissions"}
{include file="common/header.tpl"}

<div class="formSectionTitle">Submission</div>
<div class="formSection">
<table class="form" width="100%">
<tr>
	<td class="formLabel">Title:</td>
	<td>{$article->getArticleTitle()}</td>
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
		{foreach from=$files item=file}
			<div>{$file->getFileName()}</div>
		{foreachelse}
			<div>None</div>
		{/foreach}
	</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">Supplementary Files:</td>
	<td>
		{foreach from=$suppFiles item=suppFile}
			<div>{$suppFile->getTitle()}</div>
		{foreachelse}
			<div>None</div>
		{/foreach}
	</td>
	<td align="right">[<a href="">Add Supplementary File</a>]</td>
</tr>
</table>
</div>

<br />
<br />

<div class="formSectionTitle">Peer Review</div>
<div class="formSection">
<table class="plain" width="100%">
<tr>
	<td width="5%">&nbsp;</td>
	<td width="50%">&nbsp;</td>
	<td width="15%">Request</td>
	<td width="15%">Accept</td>
	<td width="15%">Due</td>
</tr>
{foreach from=$reviewAssignments item=reviewAssignment}
	<tr>
		<td class="formLabel" width="5%">A.</td>
		<td class="formLabel" width="50%">Reviewer</td>
		<td class="formLabel" width="15%">d/m/y</td>
		<td class="formLabel" width="15%">d/m/y</td>
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
		{foreach from=$editors item=editor}
			<div>{$editor->getFullName()}</div>
		{/foreach}
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
<form method="post" action="">
<tr>
	<td></td>
	<td>
		<input type="file" name="revisedFile">
		<input type="submit" name="submit" value="Upload">
	</td>
</tr>
</form>
</table>
</div>
{include file="common/footer.tpl"}
