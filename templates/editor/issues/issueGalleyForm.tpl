{**
 * templates/editor/issues/issueGalleyForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to add/edit an issue galley.
 *}
{strip}
{assign var="pageTitle" value="editor.issues.galley"}
{include file="common/header.tpl"}
{/strip}
<div id="galleyForm">
<h3>{if $galleyId}{translate key="submission.layout.editGalley"}{else}{translate key="submission.layout.addGalley"}{/if}</h3>

<br />

<form method="post" action="{url op="saveIssueGalley" path=$issueId|to_array:$galleyId}" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}
<div id="galleyFileData">
<p>{translate key="submission.layout.galleyFileData"}</p>
<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="label" required="true" key="submission.layout.galleyLabel"}</td>
	<td width="80%" class="value"><input type="text" id="label" name="label" value="{$label|escape}" size="40" maxlength="32" class="textField" /></td>
</tr>

{if $enablePublicGalleyId}
	<tr valign="top">
		<td class="label">{fieldLabel name="publicGalleyId" key="submission.layout.publicGalleyId"}</td>
		<td class="value"><input type="text" name="publicGalleyId" id="publicGalleyId" value="{$publicGalleyId|escape}" size="20" maxlength="255" class="textField" /></td>
	</tr>
{/if}{* $enablePublicGalleyId *}

<tr valign="top">
	<td>&nbsp;</td>
	<td class="instruct">{translate key="submission.layout.galleyLabelInstructions"}</td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="galleyLocale" required="true" key="common.language"}</td>
	<td class="value">
		<select name="galleyLocale" id="galleyLocale" class="selectMenu">
		{html_options options=$supportedLocales selected=$galleyLocale|default:$formLocale}
		</select>
	</td>
</tr>

<tr valign="top">
	<td class="label">{translate key="common.fileName"}</td>
	<td class="value"><a class="action" href="{url op="downloadIssueFile" path=$issueId|to_array:$galley->getFileId()}">{$galley->getFileName()|escape}</a>&nbsp;</td>
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
</div>

<br />

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="issueGalleys" path=$issueId escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>
</div>
{include file="common/footer.tpl"}
