{**
 * submission.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the details of a submission.
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
	<td>{$submission->getTitle()}</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">Author(s):</td>
	<td>
		{foreach from=$submission->getAuthors() item=author}
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
	<td>
		<form method="post" action="{$pageUrl}/editor/changeSection">
		<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
		<select name="sectionId">
		{foreach from=$sections item=section}
			<option value="{$section->getSectionId()}" {if $section->getTitle() eq $submission->getSectionTitle()}selected="selected"{/if}>{$section->getTitle()}</option>
		{/foreach}
		</select>
		<input type="submit" value="Change Section">
		</form>
	</td>
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
	<td width="30%">&nbsp;</td>
	<td width="15%" class="label">Request</td>
	<td width="15%" class="label">Accept</td>
	<td width="15%" class="label">Due</td>
	<td width="15%" class="label">Thank</td>
	<td width="5%" class="label"></td>
</tr>
{assign var="start" value="A"|ord} 
{assign var="numReviewAssignments" value=$reviewAssignments|@count} 
{foreach from=$reviewAssignments item=reviewAssignment key=key}
	<tr class="{cycle values="row,rowAlt"}">
		<td width="5%" valign="top">{$key+$start|chr}.</td>
		<td width="30%" valign="top">
			<div>{$reviewAssignment->getReviewerFullName()}</div>
			<div>[<a href="">Reviewer Comments</a>]</div>
		</td>
		<td width="15%" valign="top">{$reviewAssignment->getDateNotified()}</td>
		<td width="15%" valign="top">{$reviewAssignment->getDateConfirmed()}</td>
		<td width="15%" valign="top">d/m/y</td>
		<td width="15%" valign="top">{$reviewAssignment->getDateAcknowledged()}</td>
		<td width="5%" valign="top"><a href="{$pageUrl}/editor/clearReviewer/{$reviewAssignment->getArticleId()}/{$reviewAssignment->getReviewId()}">Clear</a></td>
	</tr>
{/foreach}
{section name="selectReviewer" start=0 loop=$numSelectReviewers}
	<tr class="{cycle values="row,rowAlt"}">
		<td width="5%">{$smarty.section.selectReviewer.index+$numReviewAssignments+$start|chr}.</td>
		<td width="30%"><a href="{$pageUrl}/editor/selectReviewer/{$submission->getArticleId()}">Select Reviewer</a></td>
		<td width="15%">d/m/y</td>
		<td width="15%">d/m/y</td>
		<td width="15%">d/m/y</td>
		<td width="15%">d/m/y</td>
		<td width="5%"></td>
	</tr>
{/section}
</table>
</div>

<br />
<br />

<div class="formSectionTitle">Editor Review</div>
<div class="formSection">
<table class="form">
<tr>
	<td class="formLabel">Editor:</td>
	<td>{$editor->getFullName()}</td>
</tr>
<tr>
	<td></td>
	<td>[<a href="">Editor/Author Comments</a>]</td>
</tr>
<tr>
	<td class="formLabel">Decision:</td>
	<td>
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
		<input type="submit" name="submit" value="Record Decision">
		</form>		
	{/if}
	</td>
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
