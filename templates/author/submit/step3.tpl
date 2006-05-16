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

{assign var="pageTitle" value="author.submit.step3"}
{include file="author/submit/submitHeader.tpl"}

<form method="post" action="{url op="saveSubmit" path=$submitStep}" enctype="multipart/form-data">
<input type="hidden" name="articleId" value="{$articleId}" />
{include file="common/formErrors.tpl"}

<p>{translate key="author.submit.uploadInstructions" supportName=$journalSettings.supportName supportEmail=$journalSettings.supportEmail supportPhone=$journalSettings.supportPhone}<br/>
<a class="action" href="javascript:openHelp('{get_help_id key="editorial.sectionEditorsRole.review.blindPeerReview" url="true"}')">{translate key="reviewer.article.ensuringBlindReview"}</a>
</p>

<div class="separator"></div>

<h3>{translate key="author.submit.submissionFile"}</h3>
<table class="data" width="100%">
{if $submissionFile}
<tr valign="top">
	<td width="20%" class="label">{translate key="common.fileName"}</td>
	<td width="80%" class="value"><a href="{url op="download" path=$articleId|to_array:$submissionFile->getFileId()}">{$submissionFile->getFileName()|escape}</a></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{translate key="common.originalFileName"}</td>
	<td width="80%" class="value">{$submissionFile->getOriginalFileName()|escape}</td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{translate key="common.fileSize"}</td>
	<td width="80%" class="value">{$submissionFile->getNiceFileSize()}</td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{translate key="common.dateUploaded"}</td>
	<td width="80%" class="value">{$submissionFile->getDateUploaded()|date_format:$datetimeFormatShort}</td>
</tr>
{else}
<tr valign="top">
	<td colspan="2" class="nodata">{translate key="author.submit.noSubmissionFile"}</td>
</tr>
{/if}
</table>

<div class="separator"></div>

<table class="data" width="100%">
<tr>
	<td width="30%" class="label">
		{if $submissionFile}
			{fieldLabel name="submissionFile" key="author.submit.replaceSubmissionFile"}
		{else}
			{fieldLabel name="submissionFile" key="author.submit.uploadSubmissionFile"}
		{/if}
	</td>
	<td width="70%" class="value"><input type="file" class="uploadField" name="submissionFile" id="submissionFile" /> <input name="uploadSubmissionFile" type="submit" class="button" value="{translate key="common.upload"}" /></td>
</tr>
</table>

<div class="separator"></div>

<p><input type="submit"{if !$submissionFile} onclick="return confirm('{translate|escape:"javascript" key="author.submit.noSubmissionConfirm"}')"{/if} value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="confirmAction('{url page="author"}', '{translate|escape:"javascript" key="author.submit.cancelSubmission"}')" /></p>

</form>

{include file="common/footer.tpl"}
