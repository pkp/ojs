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

{include file="author/submit/submitHeader.tpl"}

<div class="subTitle">{translate key="manager.setup.stepNumber" step=3}: {translate key="author.submit.upload"}</div>

<br />

<form method="post" action="{$pageUrl}/author/saveSubmit/{$submitStep}" enctype="multipart/form-data">
<input type="hidden" name="articleId" value="{$articleId}" />
{include file="common/formErrors.tpl"}

<span class="formRequired">{translate key="form.required"}</span>
<br /><br />

<div class="formSectionTitle">3.1 {translate key="author.submit.upload"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="author.submit.uploadInstructions" supportName=$journalSettings.supportName supportEmail=$journalSettings.supportEmail supportPhone=$journalSettings.supportPhone}</div>

<br />

<div class="formSubSectionTitle">{translate key="author.submit.submissionFile"}</div>
<div class="formSectionIndent">
<table class="infoTable">
{if $submissionFile}
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td><a href="{$pageUrl}/author/getFile/$articleId/{$submissionFile->getFileId()}">{$submissionFile->getFileName()}</a></td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.fileSize"}:</td>
	<td>{$submissionFile->getNiceFileSize()}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$submissionFile->getDateUploaded()|date_format:$datetimeFormatShort}</td>
</tr>
{else}
<tr>
	<td colspan="2" class="noResults">{translate key="author.submit.noSubmissionFile"}</td>
</tr>
{/if}
</table>
</div>

<br />

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="upload"}{translate key="author.submit.uploadSubmissionFile"}:{/formLabel}</td>
	<td class="formField"><input type="file" name="submissionFile" class="textField" /><input name="uploadSubmissionFile" type="submit" value="{translate key="common.upload"}" /></td>
</tr>
</table>
</div>

<br />

<table class="form">
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.continue"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="confirmAction('{$pageUrl}/author', '{translate|escape:"javascript" key="author.submit.cancelSubmission"}')" /></td>
</tr>
</table>

</form>

{include file="common/footer.tpl"}