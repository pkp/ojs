{**
 * galleyView.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Read-only view of galley information.
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submission"}
{assign var="pageId" value="submission.galley.galley"}
{include file="common/header.tpl"}

<div class="subTitle">{translate key="submission.layout.galley"}</div>

<br /><br />

<div class="formSectionTitle">{translate key="submission.layout.galleyFileData"}</div>
<div class="formSection">
<table class="form">
<tr>
	<td class="formLabel">{translate key="submission.layout.galleyLabel"}:</td>
	<td class="formField">{$galley->getLabel()}</td>
</tr>
</table>

<div class="formSectionIndent">
<table class="infoTable">
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td><a href="{$requestPageUrl}/downloadFile/{$articleId}/{$galley->getFileId()}">{$galley->getFileName()}</a></td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.fileType"}:</td>
	<td>{$galley->getFileType()}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.fileSize"}:</td>
	<td>{$galley->getNiceFileSize()}</td>
</tr>
</table>
</div>
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
{else}
<tr>
	<td colspan="2" class="noResults">{translate key="submission.layout.noStyleFile"}</td>
</tr>
</table>
{/if}
</div>

<br />

<div class="formSubSectionTitle">{translate key="submission.layout.galleyImages"}</div>

<div class="formSectionIndent">
<table width="100%">
<tr class="heading">
	<td width="40%">{translate key="common.fileName"}</td>
	<td width="20%">{translate key="common.originalFileName"}</td>
	<td width="20%">{translate key="common.fileSize"}</td>
	<td width="20%">{translate key="common.dateUploaded"}</td>
</tr>
{foreach from=$galley->getImageFiles() item=imageFile}
<tr class="{cycle values="row,rowAlt"}">
	<td><a href="{$requestPageUrl}/downloadFile/{$articleId}/{$imageFile->getFileId()}">{$imageFile->getFileName()}</a></td>
	<td>{$imageFile->getOriginalFileName()}</td>
	<td>{$imageFile->getNiceFileSize()}</td>
	<td>{$imageFile->getDateUploaded()|date_format:$datetimeFormatShort}</td>
</tr>
{foreachelse}
<tr>
<td colspan="5" class="noResults">{translate key="submission.layout.galleyNoImages"}</td>
</tr>
{/foreach}
</table>
</div>
</div>
{/if}

</form>

{include file="common/footer.tpl"}
