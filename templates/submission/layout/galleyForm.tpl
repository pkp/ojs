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
{include file="common/header.tpl"}

<h3>{if $galleyId}{translate key="submission.layout.editGalley"}{else}{translate key="submission.layout.addGalley"}{/if}</h3>

<br />

<form method="post" action="{url op="saveGalley" path=$articleId|to_array:$galleyId}" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}

<p>{translate key="submission.layout.galleyFileData"}</p>
<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="label" required="true" key="submission.layout.galleyLabel"}</td>
	<td width="80%" class="value"><input type="text" id="label" name="label" value="{$label|escape}" size="40" maxlength="32" class="textField" /></td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td class="instruct">{translate key="submission.layout.galleyLabelInstructions"}</td>
</tr>

<tr valign="top">
	<td class="label">{translate key="common.fileName"}</td>
	<td class="value"><a class="action" href="{url op="downloadFile" path=$articleId|to_array:$galley->getFileId()}">{$galley->getFileName()|escape}</a></td>
</tr>
<tr valign="top">
	<td class="label">{translate key="common.originalFileName"}</td>
	<td class="value">{$galley->getOriginalFileName()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="common.fileType"}</td>
	<td class="value">{$galley->getFileType()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="common.fileSize"}</td>
	<td class="value">{$galley->getNiceFileSize()}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="common.dateUploaded"}</td>
	<td class="value">{$galley->getDateUploaded()|date_format:$dateFormatShort}</td>
</tr>

<tr valign="top">
	<td class="label">{if $galleyId}{fieldLabel name="galleyFile" key="layoutEditor.galley.replaceGalley"}{else}{fieldLabel name="galleyFile" key="common.upload"}{/if}</td>
	<td class="value">
		<input type="file" name="galleyFile" id="galleyFile" class="uploadField" />&nbsp;&nbsp;{translate key="form.saveToUpload"}
	</td>
</tr>
</table>
<br/>
{if $galley->isHTMLGalley()}

<h3>{translate key="submission.layout.galleyHTMLData"}</h3>

<p><strong>{translate key="submission.layout.galleyStylesheet"}</strong></p>

{assign var=styleFile value=$galley->getStyleFile()}

<table class="data" width="100%">
{if $styleFile}
<tr valign="top">
	<td width="20%" class="label">{translate key="common.fileName"}</td>
	<td width="80%" class="value"><a class="action" href="{url op="downloadFile" path=$articleId|to_array:$styleFile->getFileId()}">{$styleFile->getFileName()|escape}</a></td>
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
	<td>&nbsp;</td>
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

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="styleFile" key="common.upload"}</td>
	<td class="value">
		<input type="file" name="styleFile" id="styleFile" class="uploadField" />&nbsp;&nbsp;{translate key="form.saveToUpload"}
	</td>
</tr>
</table>

<br />

<p><strong>{translate key="submission.layout.galleyImages"}</strong></p>

<table width="100%" class="listing">
<tr><td colspan="6" class="headseparator">&nbsp;</td></tr>
<tr class="heading" valign="bottom">
	<td width="25%">{translate key="common.fileName"}</td>
	<td width="25%">{translate key="common.originalFileName"}</td>
	<td width="20%">{translate key="common.fileSize"}</td>
	<td width="20%">{translate key="common.dateUploaded"}</td>
	<td width="10%" align="right">{translate key="common.action"}</td>
</tr>
<tr><td colspan="6" class="headseparator">&nbsp;</td></tr>
{foreach name=images from=$galley->getImageFiles() item=imageFile}
<tr valign="top">
	<td><a class="action" href="{url op="downloadFile" path=$articleId|to_array:$imageFile->getFileId()}">{$imageFile->getFileName()|escape}</a></td>
	<td>{$imageFile->getOriginalFileName()|escape}</td>
	<td>{$imageFile->getNiceFileSize()}</td>
	<td>{$imageFile->getDateUploaded()|date_format:$dateFormatShort}</td>
	<td align="right"><input type="button" name="deleteImage[{$imageFile->getFileId()}]" value="{translate key="common.delete"}" class="button" onclick="return confirmAction('', '{translate|escape:"javascript" key="submission.layout.confirmDeleteGalleyImage"}')" /></td>
</tr>
<tr>
	<td colspan="6" class="{if $smarty.foreach.images.last}end{/if}separator">&nbsp;</td>
</tr>
{foreachelse}
<tr>
	<td colspan="6" class="nodata">{translate key="submission.layout.galleryNoImages"}</td>
</tr>
<tr>
	<td colspan="6" class="endseparator">&nbsp;</td>
</tr>
{/foreach}
</table>

<input type="file" name="imageFile" class="uploadField" />&nbsp;
<input type="submit" name="uploadImage" class="button" value="{translate key="common.upload"}" />

{/if}

<br />

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="submissionEditing" path=$articleId escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
