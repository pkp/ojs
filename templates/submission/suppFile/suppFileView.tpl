{**
 * suppFileView.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Read-only view of supplementary file information.
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submission"}
{assign var="pageId" value="submission.suppFile.suppFile"}
{include file="common/header.tpl"}

<div class="subTitle">{translate key="article.suppFile"}</div>

<br /><br />

<div class="formSectionTitle">{translate key="author.submit.supplementaryFileData"}</div>
<div class="formSection">

<div class="formSubSectionTitle">{translate key="author.submit.supplementaryFileData"}</div>
<div class="formSectionDesc">{translate key="author.submit.supplementaryFileDataDescription"}</div>
<table class="form">
<tr>
	<td class="formLabel">{translate key="common.title"}:</td>
	<td class="formField">{$suppFile->getTitle()}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="author.submit.suppFile.createrOrOwner"}:</td>
	<td class="formField">{$suppFile->getCreator()}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="common.subject"}:</td>
	<td class="formField">{$suppFile->getSubject()}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="common.type"}:</td>
	<td class="formField">{$suppFile->getType()}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="author.submit.suppFile.briefDescription"}:</td>
	<td class="formField">{$suppFile->getDescription()|nl2br}/td>
</tr>
<tr>
	<td class="formLabel">{translate key="common.publisher"}:</td>
	<td class="formField">{$suppFile->getPublisher()}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="author.submit.suppFile.contributorOrSponsor"}:</td>
	<td class="formField">{$suppFile->getSponsor()}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="common.date"}:</td>
	<td class="formField">{$suppFile->getDateCreated()}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="common.source"}:</td>
	<td class="formField">{$suppFile->getSource()}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="common.language"}:</td>
	<td class="formField">{$suppFile->getLanguage()}</td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">{translate key="author.submit.supplementaryFileUpload"}</div>
<div class="formSection">

<div class="formSectionIndent">
<table class="infoTable">
{if $suppFile}
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td><a href="{$pageUrl}/{$roleId}/getFile/{$articleId}/{$suppFile->getFileId()}">{$suppFile->getFileName()}</a></td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.originalFileName"}:</td>
	<td>{$suppFile->getOriginalFileName()}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.fileSize"}:</td>
	<td>{$suppFile->getNiceFileSize()}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$suppFile->getDateUploaded()}</td>
</tr>
</table>
{else}
<tr>
	<td colspan="2" class="noResults">{translate key="author.submit.suppFile.noFile"}</td>
</tr>
</table>
{/if}
</div>
</table>
</div>
</form>

{include file="common/footer.tpl"}