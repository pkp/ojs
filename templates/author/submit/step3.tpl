{**
 * step3.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 3 of author article submission.
 *
 * $Id$
 *}

{assign var="pageId" value="author.submit.step3"}
{assign var="pageTitle" value="author.submit.step3"}
{include file="author/submit/submitHeader.tpl"}

<form method="post" action="{$pageUrl}/author/saveSubmit/{$submitStep}" enctype="multipart/form-data">
<input type="hidden" name="articleId" value="{$articleId}" />
{include file="common/formErrors.tpl"}

<h3>{translate key="author.submit.upload"}</h3>
<p>{translate key="author.submit.uploadInstructions" supportName=$journalSettings.supportName supportEmail=$journalSettings.supportEmail supportPhone=$journalSettings.supportPhone}</h3>

<div class="separator"></div>

<h3>{translate key="author.submit.submissionFile"}</h3>
<table class="data">
{if $submissionFile}
<tr valign="top">
	<td class="label">{translate key="common.fileName"}:</td>
	<td class="value"><a href="{$pageUrl}/author/download/{$articleId}/{$submissionFile->getFileId()}">{$submissionFile->getFileName()}</a></td>
</tr>
<tr valign="top">
	<td class="label">{translate key="common.originalFileName"}:</td>
	<td class="value">{$submissionFile->getOriginalFileName()}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="common.fileSize"}:</td>
	<td class="value">{$submissionFile->getNiceFileSize()}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="common.dateUploaded"}:</td>
	<td class="value">{$submissionFile->getDateUploaded()|date_format:$datetimeFormatShort}</td>
</tr>
{else}
<tr valign="top">
	<td colspan="2" class="nodata">{translate key="author.submit.noSubmissionFile"}</td>
</tr>
{/if}
</table>

<div class="separator"></div>

<table class="data">
<tr valign="top">
	<td class="label">{fieldLabel name="submissionFile" key="author.submit.uploadSubmissionFile"}</td>
	<td class="value"><input type="file" name="submissionFile" /><input name="uploadSubmissionFile" type="submit" class="button" value="{translate key="common.upload"}" /></td>
</tr>
</table>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /><input type="button" value="{translate key="common.cancel"}" class="button" onclick="confirmAction('{$pageUrl}/author', '{translate|escape:"javascript" key="author.submit.cancelSubmission"}')" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
