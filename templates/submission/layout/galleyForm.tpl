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

{assign var="pageTitle" value="submission.galley"}
{assign var="pageId" value="submission.galley.galley"}
{include file="common/header.tpl"}

<h3>{if $galleyId}{translate key="submission.layout.editGalley"}{else}{translate key="submission.layout.addGalley"}{/if}</h3>

<br />

<form method="post" action="{$requestPageUrl}/saveGalley/{$articleId}/{$galleyId}" enctype="multipart/form-data" disabled="disabled">
{include file="common/formErrors.tpl"}

<p>{translate key="submission.layout.galleyFileData"}</p>
<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="label" required="true" key="submission.layout.galleyLabel"}</td>
	<td width="80%" class="value"><input type="text" id="label" name="label" value="{$label|escape}" size="40" maxlength="32" class="textField" /></td>
</tr>
<tr valign="top">
	<td></td>
	<td class="instruct">{translate key="submission.layout.galleyLabelInstructions"}</td>
</tr>

<tr valign="top">
	<td class="label">{translate key="common.fileName"}</td>
	<td class="value"><a class="action" href="{$requestPageUrl}/downloadFile/{$articleId}/{$galley->getFileId()}">{$galley->getFileName()}</a></td>
</tr>
<tr valign="top">
	<td class="label">{translate key="common.originalFileName"}</td>
	<td class="value">{$galley->getOriginalFileName()}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="common.fileType"}</td>
	<td class="value">{$galley->getFileType()}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="common.fileSize"}</td>
	<td class="value">{$galley->getNiceFileSize()}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="common.dateUploaded"}:</td>
	<td class="value">{$galley->getDateUploaded()|date_format:$dateFormatShort}</td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="galleyFile" key="common.upload"}</td>
	<td class="formField"><input type="file" name="galleyFile" id="galleyFile" class="textField" /></td>
</tr>
</table>
<br/>
{if $galley->isHTMLGalley()}

<h3>{translate key="submission.layout.galleyHTMLData"}</h3>
<strong>{translate key="submission.layout.galleyStylesheet"}</strong><br/>
{assign var=styleFile value=$galley->getStyleFile()}

<table class="data" width="100%">
{if $styleFile}
<tr valign="top">
	<td width="20%" class="label">{translate key="common.fileName"}</td>
	<td width="80%" class="value"><a class="action" href="{$requestPageUrl}/downloadFile/{$articleId}/{$styleFile->getFileId()}">{$styleFile->getFileName()}</a></td>
</tr>
<tr valign="top">
	<td class="label">{translate key="common.fileSize"}</td>
	<td class="value">{$styleFile->getNiceFileSize()}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="common.dateUploaded"}</td>
	<td class="value">{$styleFile->getDateUploaded()|date_format:$dateFormatShort}</td>
</tr>
<tr valign="top">
	<td></td>
	<td class="value">
		<input type="checkbox" name="deleteStyleFile" value="1"{if $deleteStyleFile} checked="checked"{/if} />&nbsp;
		{translate key="submission.layout.deleteGalleyStylesheet"}
	</td>
</tr>
{else}
<tr valign="top">
	<td class="nodata">{translate key="submission.layout.noStyleFile"}</td>
</tr>
{/if}
</table>

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="stylesheetFile"}{translate key="common.upload"}:{/formLabel}</td>
	<td class="formField"><input type="file" name="styleFile" class="textField" /></td>
</tr>
</table>

<br />

<strong>{translate key="submission.layout.galleyImages"}</strong>

<table width="100%" class="listing">
<tr><td colspan="6" class="headseparator"></td></tr>
<tr class="heading" valign="top">
	<td width="25%">{translate key="common.fileName"}</td>
	<td width="25%">{translate key="common.originalFileName"}</td>
	<td width="20%">{translate key="common.fileSize"}</td>
	<td width="20%">{translate key="common.dateUploaded"}</td>
	<td width="10%">{translate key="common.action"}</td>
</tr>
<tr><td colspan="6" class="headseparator"></td></tr>
{foreach name=images from=$galley->getImageFiles() item=imageFile}
<tr valign="top">
	<td><a class="action" href="{$requestPageUrl}/downloadFile/{$articleId}/{$imageFile->getFileId()}">{$imageFile->getFileName()}</a></td>
	<td>{$imageFile->getOriginalFileName()}</td>
	<td>{$imageFile->getNiceFileSize()}</td>
	<td>{$imageFile->getDateUploaded()|date_format:$dateFormatShort}</td>
	<td><input type="button" name="deleteImage[{$imageFile->getFileId()}]" value="{translate key="common.delete"}" class="button" onClick="return confirmAction('', '{translate|escape:"javascript" key="submission.layout.confirmDeleteGalleyImage"}')" /></td>
</tr>
<tr>
	<td colspan="6" class="{if $smarty.foreach.images.last}end{/if}separator"></td>
</tr>
{foreachelse}
<tr>
	<td colspan="6" class="nodata">{translate key="submission.layout.galleryNoImages"}</td>
</tr>
<tr>
	<td colspan="6" class="endseparator"></td>
</tr>
{/foreach}
</table>

<input type="file" name="imageFile" class="textField" />&nbsp;
<input type="submit" name="uploadImage" class="button" value="{translate key="common.upload"}" />

{/if}

<br />

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$requestPageUrl}/submissionEditing/{$articleId}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
