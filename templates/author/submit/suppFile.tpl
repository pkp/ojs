{**
 * templates/author/submit/suppFile.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Add/edit a supplementary file.
 *
 *}
{assign var="pageTitle" value="author.submit.step4a"}
{include file="author/submit/submitHeader.tpl"}

<p><a href="{url op="submit" path=4 articleId=$articleId}">&lt;&lt; {translate key="author.submit.backToSupplementaryFiles"}</a></p>

<form id="submit" method="post" action="{url op="saveSubmitSuppFile" path=$suppFileId}" enctype="multipart/form-data">
<input type="hidden" name="articleId" value="{$articleId|escape}" />
{include file="common/formErrors.tpl"}

{if count($formLocales) > 1}
<div id="locale">
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"submitFormUrl" path=$suppFileId articleId=$articleId escape=false}
			{form_language_chooser form="submit" url=$submitFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
</div>
{/if}
<div id="supplementaryFileData">
<h3>{translate key="author.submit.supplementaryFileData"}</h3>

<p>{translate key="author.submit.supplementaryFileDataDescription"}</p>

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel required="true" name="title" key="common.title"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="title[{$formLocale|escape}]" id="title" value="{$title[$formLocale]|escape}" size="60" maxlength="255" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="creator" key="author.submit.suppFile.createrOrOwner"}</td>
	<td width="80%" class="value"><input type="text" name="creator[{$formLocale|escape}]" class="textField" id="creator" value="{$creator[$formLocale]|escape}" size="60" maxlength="255" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="subject" key="common.keywords"}</td>
	<td width="80%" class="value"><input type="text" name="subject[{$formLocale|escape}]" class="textField" id="subject" value="{$subject[$formLocale]|escape}" size="60" maxlength="255" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="type" key="common.type"}</td>
	<td width="80%" class="value"><select name="type" class="selectMenu" id="type" size="1">{html_options_translate output=$typeOptionsOutput values=$typeOptionsValues translateValues="true" selected=$type}</select><br /><label for="typeOther">{translate key="author.submit.suppFile.specifyOtherType"}</label> <input type="text" name="typeOther[{$formLocale|escape}]" id="typeOther" class="textField" value="{$typeOther[$formLocale]|escape}" size="45" maxlength="255" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="description" key="author.submit.suppFile.briefDescription"}</td>
	<td width="80%" class="value"><textarea name="description[{$formLocale|escape}]" class="textArea" id="description" rows="5" cols="60">{$description[$formLocale]|escape}</textarea></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="publisher" key="common.publisher"}</td>
	<td width="80%" class="value"><input type="text" name="publisher[{$formLocale|escape}]" class="textField" id="publisher" value="{$publisher[$formLocale]|escape}" size="60" maxlength="255" />
	</td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td><span class="instruct">{translate key="author.submit.suppFile.publisherDescription"}</span></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="sponsor" key="author.submit.suppFile.contributorOrSponsor"}</td>
	<td width="80%" class="value"><input type="text" name="sponsor[{$formLocale|escape}]" class="textField" id="sponsor" value="{$sponsor[$formLocale]|escape}" size="60" maxlength="255" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="dateCreated" key="common.date"}</td>
	<td width="80%" class="value"><input type="text" name="dateCreated" class="textField" id="dateCreated" value="{$dateCreated|escape}" size="11" maxlength="10" /> {translate key="submission.date.yyyymmdd"}</td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td><span class="instruct">{translate key="author.submit.suppFile.dateDescription"}</span></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="source" key="common.source"}</td>
	<td width="80%" class="value"><input type="text" name="source[{$formLocale|escape}]" class="textField" id="source" value="{$source[$formLocale]|escape}" size="60" maxlength="255" /></td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td><span class="instruct">{translate key="author.submit.suppFile.sourceDescription"}</span></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="language" key="common.language"}</td>
	<td width="80%" class="value"><input type="text" name="language" class="textField" id="language" value="{$language|escape}" size="5" maxlength="10" /></td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td><span class="instruct">{translate key="author.submit.languageInstructions"}</span></td>
</tr>
</table>
</div>
<div class="separator"></div>

{call_hook name="Templates::Author::Submit::SuppFile::AdditionalMetadata"}

<div id="supplementaryFileUpload">
<h3>{translate key="author.submit.supplementaryFileUpload"}</h3>

<table id="suppFile" class="data" width="100%">
{if $suppFile && $suppFile->getFileId()}
<tr valign="top">
	<td width="20%" class="label">{translate key="common.fileName"}</td>
	<td width="80%" class="value"><a href="{url op="download" path=$articleId|to_array:$suppFile->getFileId()}">{$suppFile->getFileName()|escape}</a></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{translate key="common.originalFileName"}</td>
	<td width="80%" class="value">{$suppFile->getOriginalFileName()|escape}</td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{translate key="common.fileSize"}</td>
	<td width="80%" class="value">{$suppFile->getNiceFileSize()}</td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{translate key="common.dateUploaded"}</td>
	<td width="80%" class="value">{$suppFile->getDateUploaded()|date_format:$datetimeFormatShort}</td>
</tr>
</table>

<table id="showReviewers" class="data" width="100%">
<tr valign="top">
	<td>&nbsp;</td>
	<td class="value"><input type="checkbox" name="showReviewers" id="showReviewers" value="1"{if $showReviewers==1} checked="checked"{/if} />
	<label for="showReviewers">{translate key="author.submit.suppFile.availableToPeers"}</label></td>
</tr>
</table>
{else}
<tr valign="top">
	<td colspan="2" class="nodata">{translate key="author.submit.suppFile.noFile"}</td>
</tr>
</table>
{/if}

<div class="separator"></div>

<table id="replaceFile" class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">
		{if $suppFile && $suppFile->getFileId()}
			{fieldLabel name="uploadSuppFile" key="common.replaceFile"}
		{else}
			{fieldLabel name="uploadSuppFile" key="common.upload"}
		{/if}
	</td>
	<td width="80%" class="value"><input type="file" name="uploadSuppFile" id="uploadSuppFile" class="uploadField" />&nbsp;&nbsp;{translate key="form.saveToUpload"}</td>
</tr>
{if not ($suppFile && $suppFile->getFileId())}
<tr valign="top">
	<td>&nbsp;</td>
        <td class="value"><input type="checkbox" name="showReviewers" id="showReviewers" value="1"{if $showReviewers==1} checked="checked"{/if} />&nbsp;
	<label for="showReviewers">{translate key="author.submit.suppFile.availableToPeers"}</label></td>
</tr>
{/if}
</table>
</div>
<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="submit" path="4" articleId=$articleId escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}

