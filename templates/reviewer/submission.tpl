{**
 * submission.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the reviewer administration page.
 *
 * Note: Still missing most of the data to display on this screen.
 *		 Also, text is not localized.
 *
 * $Id$
 *}

{assign var="pageTitle" value="reviewer.journalReviewer"}
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
	<td>{$submission->getSectionTitle()}</td>
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
	<td align="right"></td>
</tr>
{if not $confirmedStatus}
<tr>
	<td class="formLabel">Notify The Editor:<br />(before d/m/y)</td>
	<td colspan="2">
	<form method="post" action="{$pageUrl}/reviewer/confirmReview">
		<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
		<input type="submit" name="acceptReview" value="Can do the review">
		<input type="submit" name="declineReview" value="Unable to do the review">
	</form>
	</td>
</tr>
{/if}
<tr>
	<td class="formLabel">Submission Editor:</td>
	<td><a href="mailto:{$editor->getEmail()}">{$editor->getFullName()}</a></td>
	<td></td>
</tr>
</table>
</div>

<br />
<br />

<div class="formSectionTitle">Peer Review</div>
<div class="formSection">
<table class="plain" width="100%">
<tr>
	<td class="label" width="40%">&nbsp;</td>
	<td class="label" width="15%">Request</td>
	<td class="label" width="15%">Accept</td>
	<td class="label" width="15%">Due</td>
	<td class="label" width="15%">Thank</td>
</tr>
<tr>
	<td width="40%">&nbsp;</td>
	<td width="15%">{$reviewAssignment->getDateNotified()}</td>
	<td width="15%">{$reviewAssignment->getDateConfirmed()}</td>
	<td width="15%">d/m/y</td>
	<td width="15%">{$reviewAssignment->getDateAcknowledged()}</td>
</tr>
</table>
<table class="plain" width="100%">
<tr>
	<td>Type or paste in review comments here:</td>
	<td>[<a href="">Reviewer Comments</a>]</td>	
	<td></td>
</tr>
<tr>
	<td>Select a recommendation:</td>
	<td>
		{if not $reviewAssignment->getRecommendation()}
			<form method="post" action="{$pageUrl}/reviewer/recordRecommendation">
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
				<select name="recommendation" {if not $confirmedStatus}disabled="disabled"{/if}>
					<option value="2">Accept</option>
					<option value="3">Accept with revisions</option>
					<option value="4">Resubmit for review</option>
					<option value="5">Resubmit elsewhere</option>
					<option value="6">Decline</option>
					<option value="7">See comments</option>
				</select>
				<input type="submit" name="submit" value="Submit Review" {if not $confirmedStatus}disabled="disabled"{/if}>
			</form>
		{else}
			<b>{$reviewAssignment->getRecommendation()}</b>
		{/if}
	</td>
</tr>

<form method="post" action="">
<tr>
	<td>Reviewer's annotated version of file (optional) A:</td>
	<td>
		<input type="file" name="anotatedFile" {if not $confirmedStatus}disabled="disabled"{/if}>
		<input type="submit" name="submit" value="Upload" {if not $confirmedStatus}disabled="disabled"{/if}></td>
</tr>
<tr>
	<td></td>
	<td>(If you want to annotate the file, for the editor, save on your hard drive and use Browse/Upload.)</td>
</tr>
</form>
</table>
</div>
{include file="common/footer.tpl"}
