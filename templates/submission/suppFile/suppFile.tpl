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

{if $suppFileId}
	{assign var="pageTitle" value="author.submit.editSupplementaryFile"}
{else}
	{assign var="pageTitle" value="author.submit.addSupplementaryFile"}
{/if}

{assign var="pageCrumbTitle" value="submission.supplementaryFiles"}
{include file="common/header.tpl"}

<form method="post" action="{$pageUrl}/{$rolePath}/saveSuppFile/{$suppFileId}" enctype="multipart/form-data">
<input type="hidden" name="articleId" value="{$articleId}" />
{include file="common/formErrors.tpl"}

<h3>{translate key="author.submit.supplementaryFileData"}</h3>
<p>{translate key="author.submit.supplementaryFileDataDescription"}</p>

<table class="data">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="title" required="true" key="common.title"}</td>
	<td width="80%" class="value"><input type="text" id="title" name="title" value="{$title|escape}" size="60" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="creator" key="author.submit.suppFile.createrOrOwner"}</td>
	<td class="value"><input type="text" id="creator" name="creator" value="{$creator|escape}" size="60" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="subject" required="true" key="common.subject"}</td>
	<td class="value"><input type="text" name="subject" value="{$subject|escape}" size="60" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="type" required="true" key="common.type"}</td>
	<td class="value"><select name="type" size="1" id="type" class="selectMenu">{html_options_translate output=$typeOptionsOutput values=$typeOptionsValues translateValues="true" selected=$type}</select><br />{translate key="author.submit.suppFile.specifyOtherType"}: <input type="text" name="typeOther" value="{$typeOther|escape}" size="45" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="description" required="true" key="author.submit.suppFile.briefDescription"}</td>
	<td class="value"><textarea name="description" id="description" rows="5" cols="60" class="textArea">{$description|escape}</textarea></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="publisher" key="common.publisher"}</td>
	<td class="value"><input type="text" name="publisher" id="publisher" value="{$publisher|escape}" size="60" maxlength="255" class="textField" />
	</td>
</tr>
<tr valign="top">
	<td></td>
	<td class="instruct">{translate key="author.submit.suppFile.publisherDescription"}</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="sponsor" key="author.submit.suppFile.contributorOrSponsor"}</td>
	<td class="value"><input id="sponsor" type="text" name="sponsor" value="{$sponsor|escape}" size="60" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="dateCreated" key="common.date"}</td>
	<td class="value"><input type="text" id="dateCreated" name="dateCreated" value="{$dateCreated|escape}" size="11" maxlength="10" class="textField" /> YYYY-MM-DD</td>
</tr>
<tr valign="top">
	<td></td>
	<td class="instruct">{translate key="author.submit.suppFile.dateDescription"}</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="source" key="common.source"}</td>
	<td class="value"><input type="text" id="source" name="source" value="{$source|escape}" size="60" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td></td>
	<td class="instruct">{translate key="author.submit.suppFile.sourceDescription"}</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="language" key="common.language"}</td>
	<td class="value"><input type="text" id="language" name="language" value="{$language|escape}" size="5" maxlength="10" class="textField" /></td>
</tr>
<tr valign="top">
	<td></td>
	<td class="instruct">{translate key="author.submit.languageInstructions"}</td>
</tr>
</table>

<br />

<div class="separator"></div>

<h3>{translate key="author.submit.supplementaryFileUpload"}</h3>

<table class="data">
	{if $suppFile}
		<tr valign="top">
			<td width="20%" class="label">{translate key="common.fileName"}:</td>
			<td width="80%" class="data"><a href="{$pageUrl}/{$roleId}/getFile/{$articleId}/{$suppFile->getFileId()}">{$suppFile->getFileName()}</a></td>
		</tr>
		<tr valign="top">
			<td class="label">{translate key="common.originalFileName"}:</td>
			<td class="data">{$suppFile->getOriginalFileName()}</td>
		</tr>
		<tr valign="top">
			<td class="label">{translate key="common.fileSize"}:</td>
			<td class="data">{$suppFile->getNiceFileSize()}</td>
		</tr>
		<tr>
			<td class="label">{translate key="common.dateUploaded"}:</td>
			<td class="data">{$suppFile->getDateUploaded()|date_format:$dateFormatShort}</td>
		</tr>
	</table>

	<table class="data">
		<tr valign="top">
			<td class="label"><input type="checkbox" name="showReviewers" value="1"{if $showReviewers} checked="checked"{/if} /></td>
			<td class="instruct">{translate key="author.submit.suppFile.availableToPeers"}</td>
		</tr>
	</table>
{else}
		<tr valign="top">
			<td colspan="2" class="nodata">{translate key="author.submit.suppFile.noFile"}</td>
		</tr>
	</table>
{/if}

<br />

<table class="data">
<tr valign="top">
	<td class="label">{fieldLabel name="uploadSuppFile" key="common.upload"}</td>
	<td class="value"><input type="file" name="uploadSuppFile" class="uploadField" /></td>
</tr>
{if not $suppFile}
<tr valign="top">
	<td class="label"><input type="checkbox" name="showReviewers" value="1"{if $showReviewers} checked="checked"{/if} /></td>
	<td class="instruct">{translate key="author.submit.suppFile.availableToPeers"}</td>
</tr>
{/if}
</table>

<br />

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$requestPageUrl}/submissionEditing/{$articleId}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
