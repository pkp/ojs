{**
 * step4.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 4 of author article submission.
 *
 * $Id$
 *}

{assign var="pageId" value="author.submit.step4"}
{assign var="pageTitle" value="author.submit.step4"}
{include file="author/submit/submitHeader.tpl"}

<form method="post" action="{$pageUrl}/author/saveSubmit/{$submitStep}">
<input type="hidden" name="articleId" value="{$articleId}" />
{include file="common/formErrors.tpl"}

<p>{translate key="author.submit.supplementaryFilesInstructions"}</p>

<table class="listing" width="100%">
<tr>
	<td colspan="6" class="headseparator"></td>
</tr>
<tr class="heading" valign="top">
	<td width="5%">{translate key="common.id"}</td>
	<td width="40%">{translate key="common.title"}</td>
	<td width="25%">{translate key="common.originalFileName"}</td>
	<td width="10%"><nobr>{translate key="common.dateUploaded"}</nobr></td>
	<td width="20%" colspan="2"></td>
</tr>
<tr>
	<td colspan="6" class="headseparator"></td>
</tr>
{foreach from=$suppFiles item=file}
<tr valign="top">
	<td>{$file->getSuppFileId()}</td>
	<td>{$file->getTitle()}</td>
	<td>{$file->getOriginalFileName()}</td>
	<td>{$file->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
	<td><a href="{$pageUrl}/author/submitSuppFile/{$file->getSuppFileId()}?articleId={$articleId}">{translate key="common.edit"}</a>
	</td>
	<td><a href="{$pageUrl}/author/deleteSubmitSuppFile/{$file->getSuppFileId()}?articleId={$articleId}" onclick="return confirm('{translate|escape:"javascript" key="author.submit.confirmDeleteSuppFile"}')">{translate key="common.delete"}</a></td>
</tr>
{foreachelse}
<tr valign="top">
<td colspan="6" class="nodata">{translate key="author.submit.noSupplementaryFiles"}</td>
</tr>
{/foreach}
</table>

<a href="{$pageUrl}/author/submitSuppFile?articleId={$articleId}" class="button">{translate key="author.submit.addSupplementaryFile"}</a>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /><input type="button" value="{translate key="common.cancel"}" class="button" onclick="confirmAction('{$pageUrl}/author', '{translate|escape:"javascript" key="author.submit.cancelSubmission"}')" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
