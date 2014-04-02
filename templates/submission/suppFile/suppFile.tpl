{**
 * templates/submission/suppFile/suppFile.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Add/edit a supplementary file.
 *
 *}
{strip}
{if $suppFileId}
	{assign var="pageTitle" value="author.submit.editSupplementaryFile"}
{else}
	{assign var="pageTitle" value="author.submit.addSupplementaryFile"}
{/if}
{assign var="pageCrumbTitle" value="submission.supplementaryFiles"}
{include file="common/header.tpl"}
{/strip}

<form id="suppFile" method="post" action="{url page=$rolePath op="saveSuppFile" path=$suppFileId}" enctype="multipart/form-data">
<input type="hidden" name="articleId" value="{$articleId|escape}" />
<input type="hidden" name="from" value="{$from|escape}" />
{include file="common/formErrors.tpl"}

{if count($formLocales) > 1}
<div id="locale">
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{if $suppFileId}{url|assign:"formUrl" op="editSuppFile" path=$articleId|to_array:$suppFileId from=$from escape=false}
			{else}{url|assign:"formUrl" op="addSuppFile" path=$articleId from=$from escape=false}
			{/if}
			{form_language_chooser form="suppFile" url=$formUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
</div>
{/if}
<div id="supplementaryFileData">
<h3>{translate key="author.submit.supplementaryFileData"}</h3>
<p>{translate key="author.submit.supplementaryFileDataDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="title" required="true" key="common.title"}</td>
		<td width="80%" class="value"><input type="text" id="title" name="title[{$formLocale|escape}]" value="{$title[$formLocale]|escape}" size="60" maxlength="255" class="textField" /></td>
	</tr>
	{if $enablePublicSuppFileId}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="publicSuppFileId" key="author.suppFile.publicSuppFileIdentifier"}</td>
		<td width="80%" class="value"><input type="text" id="publicSuppFileId" name="publicSuppFileId" value="{$publicSuppFileId|escape}" size="20" maxlength="255" class="textField" /></td>
	</tr>
	{/if}
	<tr valign="top">
		<td class="label">{fieldLabel name="creator" key="author.submit.suppFile.createrOrOwner"}</td>
		<td class="value"><input type="text" id="creator" name="creator[{$formLocale|escape}]" value="{$creator[$formLocale]|escape}" size="60" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="subject" key="common.subject"}</td>
		<td class="value"><input type="text" name="subject[{$formLocale|escape}]" id="subject" value="{$subject[$formLocale]|escape}" size="60" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="type" key="common.type"}</td>
		<td class="value"><select name="type" size="1" id="type" class="selectMenu">{html_options_translate output=$typeOptionsOutput values=$typeOptionsValues translateValues="true" selected=$type}</select><br />{translate key="author.submit.suppFile.specifyOtherType"}: <input type="text" name="typeOther[{$formLocale|escape}]" value="{$typeOther[$formLocale]|escape}" size="45" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="description" key="author.submit.suppFile.briefDescription"}</td>
		<td class="value"><textarea name="description[{$formLocale|escape}]" id="description" rows="5" cols="60" class="textArea">{$description[$formLocale]|escape}</textarea></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="publisher" key="common.publisher"}</td>
		<td class="value">
			<input type="text" name="publisher[{$formLocale|escape}]" id="publisher" value="{$publisher[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="author.submit.suppFile.publisherDescription"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="sponsor" key="author.submit.suppFile.contributorOrSponsor"}</td>
		<td class="value"><input id="sponsor" type="text" name="sponsor[{$formLocale|escape}]" value="{$sponsor[$formLocale]|escape}" size="60" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="dateCreated" key="common.date"}</td>
		<td class="value">
			<input type="text" id="dateCreated" name="dateCreated" value="{$dateCreated|escape}" size="11" maxlength="10" class="textField" /> {translate key="submission.date.yyyymmdd"}
			<br />
			<span class="instruct">{translate key="author.submit.suppFile.dateDescription"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="source" key="common.source"}</td>
		<td class="value">
			<input type="text" id="source" name="source[{$formLocale|escape}]" value="{$source[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="author.submit.suppFile.sourceDescription"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="language" key="common.language"}</td>
		<td class="value">
			<input type="text" id="language" name="language" value="{$language|escape}" size="5" maxlength="10" class="textField" />
			<br />
			<span class="instruct">{translate key="author.submit.languageInstructions"}</span>
		</td>
	</tr>
</table>
</div>
<div class="separator"></div>

{foreach from=$pubIdPlugins item=pubIdPlugin}
	{assign var=pubIdMetadataFile value=$pubIdPlugin->getPubIdMetadataFile()}
	{include file="$pubIdMetadataFile" pubObject=$suppFile}
{/foreach}

{call_hook name="Templates::Submission::SuppFile::AdditionalMetadata"}

<div id="supplementaryFileUpload">
<h3>{translate key="author.submit.supplementaryFileUpload"}</h3>

<table id="suppFile" class="data">
{if $suppFile && $suppFile->getRemoteURL()}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="remoteURL" required="true" key="submission.layout.galleyRemoteURL"}</td>
		<td width="80%" class="value"><input type="text" name="remoteURL" id="remoteURL" value="{$suppFile->getRemoteURL()|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
</table>
{else}
	{if $suppFile && $suppFile->getFileId()}
		<tr valign="top">
			<td width="20%" class="label">{translate key="common.fileName"}</td>
			<td width="80%" class="data"><a href="{url op="downloadFile" path=$articleId|to_array:$suppFile->getFileId()}">{$suppFile->getFileName()|escape}</a></td>
		</tr>
		<tr valign="top">
			<td class="label">{translate key="common.originalFileName"}</td>
			<td class="value">{$suppFile->getOriginalFileName()|escape}</td>
		</tr>
		<tr valign="top">
			<td class="label">{translate key="common.fileSize"}</td>
			<td class="value">{$suppFile->getNiceFileSize()}</td>
		</tr>
		<tr>
			<td class="label">{translate key="common.dateUploaded"}</td>
			<td class="value">{$suppFile->getDateUploaded()|date_format:$dateFormatShort}</td>
		</tr>
	</table>

	<table width="100%"  class="data">
		<tr valign="top">
			<td width="5%" class="label"><input type="checkbox" name="showReviewers" id="showReviewers" value="1"{if $showReviewers==1} checked="checked"{/if} /></td>
			<td width="95%" class="value"><label for="showReviewers">{translate key="author.submit.suppFile.availableToPeers"}</label></td>
		</tr>
	</table>
	{else}
		<tr valign="top">
			<td colspan="2" class="nodata">{translate key="author.submit.suppFile.noFile"}</td>
		</tr>
	</table>
	{/if}

	<br />

	<table id="showReviewers" width="100%" class="data">
		<tr valign="top">
			<td class="label">
				{if $suppFile && $suppFile->getFileId()}
					{fieldLabel name="uploadSuppFile" key="common.replaceFile"}
				{else}
					{fieldLabel name="uploadSuppFile" key="common.upload"}
				{/if}
			</td>
			<td class="value"><input type="file" name="uploadSuppFile" id="uploadSuppFile" class="uploadField" />&nbsp;&nbsp;{translate key="author.submit.supplementaryFiles.saveToUpload"}</td>
		</tr>
		{if not ($suppFile && $suppFile->getFileId())}
		<tr valign="top">
			<td>&nbsp;</td>
			<td class="value">
				<input type="checkbox" name="showReviewers" id="showReviewers" value="1"{if $showReviewers==1} checked="checked"{/if} />&nbsp;
				<label for="showReviewers">{translate key="author.submit.suppFile.availableToPeers"}</label>
			</td>
		</tr>
		{/if}
	</table>
	</div>
{/if}
<div class="separator"></div>


<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1)" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}

