{**
 * galleyForm.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to add/edit a galley.
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submission"}
{assign var="pageId" value="submission.galley.galley"}
{include file="common/header.tpl"}

<div class="subTitle">{if $galleyId}{translate key="submission.layout.editGalley"}{else}{translate key="submission.layout.addGalley"}{/if}</div>

<br />

<form method="post" action="{$requestPageUrl}/saveGalley/{$articleId}/{$galleyId}" enctype="multipart/form-data" disabled="disabled">
{include file="common/formErrors.tpl"}

<span class="formRequired">{translate key="form.required"}</span>
<br /><br />

<div class="formSectionTitle">{translate key="submission.layout.galleyFileData"}</div>
<div class="formSection">
<table class="form">
<tr>
	<td class="formLabel">{formLabel name="label" required="true"}{translate key="submission.layout.galleyLabel"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="label" value="{$label|escape}" size="40" maxlength="32" class="textField" /></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="submission.layout.galleyLabelInstructions"}</td>
</tr>
</table>

<div class="formSectionIndent">
<table class="infoTable">
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td><a href="{$requestPageUrl}/downloadFile/{$articleId}/{$galley->getFileId()}">{$galley->getFileName()}</a></td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.originalFileName"}:</td>
	<td>{$galley->getOriginalFileName()}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.fileType"}:</td>
	<td>{$galley->getFileType()}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.fileSize"}:</td>
	<td>{$galley->getNiceFileSize()}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$galley->getDateUploaded()|date_format:$datetimeFormatShort}</td>
</tr>
</table>
</div>

<br />

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="galleyFile"}{translate key="common.upload"}:{/formLabel}</td>
	<td class="formField"><input type="file" name="galleyFile" class="textField" /></td>
</tr>
</table>
</div>

{if $galley->isHTMLGalley()}
<br />

<div class="formSectionTitle">{translate key="submission.layout.galleyHTMLData"}</div>
<div class="formSection">

<div class="formSubSectionTitle">{translate key="submission.layout.galleyStylesheet"}</div>
{assign var=styleFile value=$galley->getStyleFile()}

<div class="formSectionIndent">
<table class="infoTable">
{if $styleFile}
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td><a href="{$requestPageUrl}/downloadFile/{$articleId}/{$styleFile->getFileId()}">{$styleFile->getFileName()}</a></td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.fileSize"}:</td>
	<td>{$styleFile->getNiceFileSize()}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$styleFile->getDateUploaded()|date_format:$datetimeFormatShort}</td>
</tr>
</table>

<table class="plain">
<tr>
	<td class="formFieldLeft"><input type="checkbox" name="deleteStyleFile" value="1"{if $deleteStyleFile} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="submission.layout.deleteGalleyStylesheet"}</td>
</tr>
</table>
{else}
<tr>
	<td colspan="2" class="noResults">{translate key="submission.layout.noStyleFile"}</td>
</tr>
</table>
{/if}

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="stylesheetFile"}{translate key="common.upload"}:{/formLabel}</td>
	<td class="formField"><input type="file" name="styleFile" class="textField" /></td>
</tr>
</table>
</div>

<br />

<div class="formSubSectionTitle">{translate key="submission.layout.galleyImages"}</div>

<div class="formSectionIndent">
<table width="100%">
<tr class="heading">
	<td width="30%">{translate key="common.fileName"}</td>
	<td width="20%">{translate key="common.originalFileName"}</td>
	<td width="20%">{translate key="common.fileSize"}</td>
	<td width="20%">{translate key="common.dateUploaded"}</td>
	<td width="10%">{translate key="common.delete"}</td>
</tr>
{foreach from=$galley->getImageFiles() item=imageFile}
<tr class="{cycle values="row,rowAlt"}">
	<td><a href="{$requestPageUrl}/downloadFile/{$articleId}/{$imageFile->getFileId()}">{$imageFile->getFileName()}</a></td>
	<td>{$imageFile->getOriginalFileName()}</td>
	<td>{$imageFile->getNiceFileSize()}</td>
	<td>{$imageFile->getDateUploaded()|date_format:$datetimeFormatShort}</td>
	<td><input type="image" name="deleteImage[{$imageFile->getFileId()}]" src="{$baseUrl}/templates/images/icons/delete.gif" width="16" height="16" border="0" onclick="return confirmAction('', '{translate|escape:"javascript" key="submission.layout.confirmDeleteGalleyImage"}')" /></td>
</tr>
{foreachelse}
<tr>
<td colspan="5" class="noResults">{translate key="submission.layout.galleyNoImages"}</td>
</tr>
{/foreach}
</table>



<table class="form">
<tr>
	<td class="formFieldLeft"><input type="file" name="imageFile" class="textField" /></td>
	<td><input type="submit" name="uploadImage" class="formButtonPlain" value="{translate key="common.upload"}" /></td>
</tr>
</table>
</div>

</div>


{/if}

<br />

<table class="form">
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.save"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$requestPageUrl}/submissionEditing/{$articleId}'" /></td>
</tr>
</table>

</form>

{include file="common/footer.tpl"}
