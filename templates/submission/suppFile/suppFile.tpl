{**
 * suppFile.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Add/edit a supplementary file.
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submission"}
{assign var="pageId" value="submission.suppFile.suppFile"}
{include file="common/header.tpl"}

<div class="subTitle">{if $suppFileID}{translate key="author.submit.editSupplementaryFile"}{else}{translate key="author.submit.addSupplementaryFile"}{/if}</div>

<br /><br />

<form method="post" action="{$pageUrl}/{$rolePath}/saveSuppFile/{$suppFileId}" enctype="multipart/form-data">
<input type="hidden" name="articleId" value="{$articleId}" />
{include file="common/formErrors.tpl"}

<span class="formRequired">{translate key="form.required"}</span>
<br /><br />

<div class="formSectionTitle">{translate key="author.submit.supplementaryFileData"}</div>
<div class="formSection">

<div class="formSubSectionTitle">{translate key="author.submit.supplementaryFileData"}</div>
<div class="formSectionDesc">{translate key="author.submit.supplementaryFileDataDescription"}</div>
<table class="form">
<tr>
	<td class="formLabel">{formLabel name="title" required="true"}{translate key="common.title"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="title" value="{$title|escape}" size="60" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="creator"}{translate key="author.submit.suppFile.createrOrOwner"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="creator" value="{$creator|escape}" size="60" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="subject" required="true"}{translate key="common.subject"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="subject" value="{$subject|escape}" size="60" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="type" required="true"}{translate key="common.type"}:{/formLabel}</td>
	<td class="formField"><select name="type" size="1" class="selectMenu">{html_options_translate output=$typeOptionsOutput values=$typeOptionsValues translateValues="true" selected=$type}</select><br />{translate key="author.submit.suppFile.specifyOtherType"}: <input type="text" name="typeOther" value="{$typeOther|escape}" size="45" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="description" required="true"}{translate key="author.submit.suppFile.briefDescription"}:{/formLabel}</td>
	<td class="formField"><textarea name="description" rows="5" cols="60" class="textArea">{$description|escape}</textarea></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="publisher"}{translate key="common.publisher"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="publisher" value="{$publisher|escape}" size="60" maxlength="255" class="textField" />
	</td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="author.submit.suppFile.publisherDescription"}</td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="sponsor"}{translate key="author.submit.suppFile.contributorOrSponsor"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="sponsor" value="{$sponsor|escape}" size="60" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="dateCreated"}{translate key="common.date"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="dateCreated" value="{$dateCreated|escape}" size="11" maxlength="10" class="textField" /> YYYY-MM-DD</td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="author.submit.suppFile.dateDescription"}</td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="source"}{translate key="common.source"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="source" value="{$source|escape}" size="60" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="author.submit.suppFile.sourceDescription"}</td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="language"}{translate key="common.language"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="language" value="{$language|escape}" size="5" maxlength="10" class="textField" /></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="author.submit.languageInstructions"}</td>
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
	<td class="infoLabel">{translate key="common.fileSize"}:</td>
	<td>{$suppFile->getNiceFileSize()}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$suppFile->getDateUploaded()}</td>
</tr>
</table>

<table class="plain">
<tr>
	<td class="formFieldLeft"><input type="checkbox" name="showReviewers" value="1"{if $showReviewers} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="author.submit.suppFile.availableToPeers"}</td>
</tr>
</table>
{else}
<tr>
	<td colspan="2" class="noResults">{translate key="author.submit.suppFile.noFile"}</td>
</tr>
</table>
{/if}
</div>

<br />

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="uploadSuppFile"}{translate key="common.upload"}:{/formLabel}</td>
	<td class="formField"><input type="file" name="uploadSuppFile" class="textField" /></td>
</tr>
{if not $suppFile}
<tr>
	<td class="formFieldLeft"><input type="checkbox" name="showReviewers" value="1"{if $showReviewers} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="author.submit.suppFile.availableToPeers"}</td>
</tr>
{/if}
</table>
</div>

<br />

<table class="form">
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.save"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/author/submit/4?articleId={$articleId}'" /></td>
</tr>
</table>

</form>

{include file="common/footer.tpl"}