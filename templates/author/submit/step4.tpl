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

{assign var="pageTitle" value="author.submit.step4"}
{include file="author/submit/submitHeader.tpl"}

<form method="post" action="{url op="saveSubmit" path=$submitStep}" enctype="multipart/form-data">
<input type="hidden" name="articleId" value="{$articleId}" />
{include file="common/formErrors.tpl"}

<p>{translate key="author.submit.supplementaryFilesInstructions"}</p>

<table class="listing" width="100%">
<tr>
	<td colspan="5" class="headseparator">&nbsp;</td>
</tr>
<tr class="heading" valign="bottom">
	<td width="5%">{translate key="common.id"}</td>
	<td width="40%">{translate key="common.title"}</td>
	<td width="25%">{translate key="common.originalFileName"}</td>
	<td width="15%" class="nowrap">{translate key="common.dateUploaded"}</td>
	<td width="15%" align="right">{translate key="common.action"}</td>
</tr>
<tr>
	<td colspan="6" class="headseparator">&nbsp;</td>
</tr>
{foreach from=$suppFiles item=file}
<tr valign="top">
	<td>{$file->getSuppFileId()}</td>
	<td>{$file->getTitle()|escape}</td>
	<td>{$file->getOriginalFileName()|escape}</td>
	<td>{$file->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
	<td align="right"><a href="{url op="submitSuppFile" path=$file->getSuppFileId() articleId=$articleId}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteSubmitSuppFile" path=$file->getSuppFileId() articleId=$articleId}" onclick="return confirm('{translate|escape:"javascript" key="author.submit.confirmDeleteSuppFile"}')" class="action">{translate key="common.delete"}</a></td>
</tr>
{foreachelse}
<tr valign="top">
	<td colspan="6" class="nodata">{translate key="author.submit.noSupplementaryFiles"}</td>
</tr>
{/foreach}
</table>

<div class="separator"></div>

<table class="data" width="100%">
<tr>
	<td width="30%" class="label">{fieldLabel name="uploadSuppFile" key="author.submit.uploadSuppFile"}</td>
	<td width="70%" class="value"><input type="file" name="uploadSuppFile" id="uploadSuppFile"  class="uploadField" /> <input name="submitUploadSuppFile" type="submit" class="button" value="{translate key="common.upload"}" /></td>
</tr>
</table>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="confirmAction('{url page="author"}', '{translate|escape:"javascript" key="author.submit.cancelSubmission"}')" /></p>

</form>

{include file="common/footer.tpl"}
