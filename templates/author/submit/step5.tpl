{**
 * step5.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 5 of author article submission.
 *
 * $Id$
 *}

{assign var="pageTitle" value="author.submit.step5"}
{include file="author/submit/submitHeader.tpl"}

<p>{translate key="author.submit.confirmationDescription" journalTitle=$journalSettings.journalTitle}</p>

<form method="post" action="{$pageUrl}/author/saveSubmit/{$submitStep}">
<input type="hidden" name="articleId" value="{$articleId}" />

<h3>{translate key="author.submit.filesSummary"}</h3>
<table class="listing" width="100%">
<tr>
	<td colspan="5" class="headseparator"></td>
</tr>
<tr class="heading" valign="top">
	<td width="10%">{translate key="common.id"}</td>
	<td width="35%">{translate key="common.originalFileName"}</td>
	<td width="25%">{translate key="common.type"}</td>
	<td width="20%"><nobr>{translate key="common.fileSize"}</nobr></td>
	<td width="10%"><nobr>{translate key="common.dateUploaded"}</nobr></td>
</tr>
<tr>
	<td colspan="5" class="headseparator"></td>
</tr>
{foreach from=$files item=file}
<tr valign="top">
	<td>{$file->getFileId()}</td>
	<td><a href="{$pageUrl}/author/download/{$articleId}/{$file->getFileId()}">{$file->getOriginalFileName()}</a></td>
	<td>{if ($file->getType() == 'supp')}{translate key="author.submit.suppFile"}{else}{translate key="author.submit.submissionFile"}{/if}</td>
	<td>{$file->getNiceFileSize()}</td>
	<td>{$file->getDateUploaded()|date_format:$dateFormatTrunc}</td>
</tr>
{foreachelse}
<tr valign="top">
<td colspan="5" class="nodata">{translate key="author.submit.noFiles"}</td>
</tr>
{/foreach}
</table>

<div class="separator"></div>

<p><input type="submit" value="{translate key="author.submit.finishSubmission"}" class="button defaultButton" /><input type="button" value="{translate key="common.cancel"}" class="button" onclick="confirmAction('{$pageUrl}/author', '{translate|escape:"javascript" key="author.submit.cancelSubmission"}')" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
