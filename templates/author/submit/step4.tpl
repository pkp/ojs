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
{include file="author/submit/submitHeader.tpl"}

<div class="subTitle">{translate key="author.submit.stepNumber" step=4}: {translate key="author.submit.supplementaryFiles"}</div>

<br />

<form method="post" action="{$pageUrl}/author/saveSubmit/{$submitStep}">
<input type="hidden" name="articleId" value="{$articleId}" />
{include file="common/formErrors.tpl"}

<span class="formRequired">{translate key="form.required"}</span>
<br /><br />

<div class="formSectionTitle">4.1 {translate key="author.submit.supplementaryFiles"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="author.submit.supplementaryFilesInstructions"}</div>

<div class="formSectionIndent">
<table width="100%">
<tr class="heading">
	<td>{translate key="common.id"}</td>
	<td width="40%">{translate key="common.title"}</td>
	<td>{translate key="common.originalFileName"}</td>
	<td><nobr>{translate key="common.dateUploaded"}</nobr></td>
	<td colspan="2"></td>
</tr>
{foreach from=$suppFiles item=file}
<tr class="{cycle values="row,rowAlt"}">
	<td>{$file->getSuppFileId()}</td>
	<td width="40%"><a href="{$pageUrl}/author/submitSuppFile/{$file->getSuppFileId()}?articleId={$articleId}">{$file->getTitle()}</a></td>
	<td>{$file->getOriginalFileName()}</td>
	<td>{$file->getDateSubmitted()|date_format:$datetimeFormatShort}</td>
	<td><a href="{$pageUrl}/author/submitSuppFile/{$file->getSuppFileId()}?articleId={$articleId}" class="tableAction">{translate key="common.edit"}</a>
	</td>
	<td><a href="{$pageUrl}/author/deleteSubmitSuppFile/{$file->getSuppFileId()}?articleId={$articleId}" onclick="return confirm('{translate|escape:"javascript" key="author.submit.confirmDeleteSuppFile"}')" class="tableAction">{translate key="common.delete"}</a></td>
</tr>
{foreachelse}
<tr>
<td colspan="6" class="noResults">{translate key="author.submit.noSupplementaryFiles"}</td>
</tr>
{/foreach}
</table>

<a href="{$pageUrl}/author/submitSuppFile?articleId={$articleId}" class="tableButton">{translate key="author.submit.addSupplementaryFile"}</a>
</div>

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