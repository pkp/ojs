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

{assign var="pageId" value="author.submit.step5"}
{assign var="pageTitle" value="author.submit.step5"}
{include file="author/submit/submitHeader.tpl"}

<p>{translate key="author.submit.confirmationDescription" journalTitle=$journalSettings.journalTitle}</p>

<form method="post" action="{$pageUrl}/author/saveSubmit/{$submitStep}">
<input type="hidden" name="articleId" value="{$articleId}" />

<h3>{translate key="author.submit.filesSummary"}</h3>
<table class="listing" width="100%">
<tr class="heading">
	<td>{translate key="common.id"}</td>
	<td>{translate key="common.originalFileName"}</td>
	<td>{translate key="common.type"}</td>
	<td><nobr>{translate key="common.fileSize"}</nobr></td>
	<td><nobr>{translate key="common.dateUploaded"}</nobr></td>
</tr>
{foreach from=$files item=file}
<tr class="{cycle values="row,rowAlt"}">
	<td>{$file->getFileId()}</td>
	<td><a href="{$pageUrl}/author/download/{$articleId}/{$file->getFileId()}">{$file->getOriginalFileName()}</a></td>
	<td>{if ($file->getType() == 'supp')}{translate key="author.submit.suppFile"}{else}{translate key="author.submit.submissionFile"}{/if}</td>
	<td>{$file->getNiceFileSize()}</td>
	<td>{$file->getDateUploaded()|date_format:$datetimeFormatShort}</td>
</tr>
{foreachelse}
<tr>
<td colspan="5" class="noResults">{translate key="author.submit.noFiles"}</td>
</tr>
{/foreach}
</table>

<br />

<table class="data">
<tr>
	<td></td>
	<td class="value"><input type="submit" value="{translate key="author.submit.finishSubmission"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="confirmAction('{$pageUrl}/author', '{translate|escape:"javascript" key="author.submit.cancelSubmission"}')" /></td>
</tr>
</table>

</form>

{include file="common/footer.tpl"}
