{**
 * step4.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 4 of author submit.
 *
 * $Id$
 *}

{assign var="pageTitle" value="author.submit"}
{include file="common/header.tpl"}

<div><a href="{$pageUrl}/author/submit/3">&lt;&lt; {translate key="manager.setup.previousStep"}</a> | <a href="{$pageUrl}/author/submit/5">{translate key="manager.setup.nextStep"} &gt;&gt;</a></div>

<br />

<div class="subTitle">{translate key="manager.setup.stepNumber" step=4}: {translate key="author.submit.supplementaryFiles"}</div>

<br />

<span class="formRequired">(* {translate key="common.required"})</span>
<form method="post" action="{$pageUrl}/manager/saveSetup/4">
{include file="common/formErrors.tpl"}

<div class="formSectionTitle">4.1 {translate key="author.submit.supplementaryFiles"}</div>
<div class="formSection">

<div class="formSubSectionTitle">{translate key="author.submit.supplementaryFileData"}</div>
<div class="formSectionDesc">{translate key="author.submit.supplementaryFileDataDescription"}</div>
<table class="form">
<tr>
	<td class="formLabel" colspan="2">*{formLabel name="title"}{translate key="common.title"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="title" value="{$title|escape}" size="70" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel" colspan="2">{formLabel name="title"}{translate key="author.submit.createrOrOwner"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="title" value="{$title|escape}" size="70" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel" colspan="2">*{formLabel name="title"}{translate key="common.subject"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="title" value="{$title|escape}" size="70" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel" colspan="2">{formLabel name="title"}{translate key="common.type"}:{/formLabel}</td>
	<td class="formField">POPUP HERE<br />{translate key="author.submit.specifyOther"}: <input type="text" name="title" value="{$title|escape}" size="50" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel" colspan="2">*{formLabel name="title"}{translate key="author.submit.briefDescription"}:{/formLabel}</td>
	<td class="formField"><textarea  name="description" rows="5" cols="60" class="textArea">{$title|escape}</textarea></td>
</tr>
<tr>
	<td class="formLabel" colspan="2">*{formLabel name="title"}{translate key="common.publisher"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="title" value="{$title|escape}" size="70" class="textField" />
	<br />
	<span class="formLabelRightPlain">{translate key="author.submit.publisher.description"}</span></td>
</tr>
<tr>
	<td class="formLabel" colspan="2">{formLabel name="title"}{translate key="author.submit.contributorOrSponsor"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="title" value="{$title|escape}" size="70" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel" colspan="2">{formLabel name="title"}{translate key="common.date"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="title" value="{$title|escape}" size="10" class="textField" /> YYYY-MM-DD
	<br />
	<span class="formLabelRightPlain">{translate key="author.submit.date.description"}</span></td>
</tr>
<tr>
	<td class="formLabel" colspan="2">{formLabel name="title"}{translate key="common.source"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="title" value="{$title|escape}" size="70" class="textField" />
	<br />
	<span class="formLabelRightPlain">{translate key="author.submit.source.description"}</span></td>
</tr>
<tr>
	<td class="formLabel" colspan="2">{formLabel name="title"}{translate key="common.language"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="title" value="{$title|escape}" size="4" class="textField" />
	<br />
	<span class="formLabelRightPlain">{translate key="author.submit.language.description"}</span></td>
</tr>
</table>

<div class="formSubSectionTitle">{translate key="author.submit.supplementaryFileUpload"}</div>
<table class="form">
<tr>
	<td class="formLabel">{formLabel name="upload"}{translate key="common.upload"}:{/formLabel}</td>
	<td class="formField"><input type="file" name="upload" /></td>
</tr>
<tr>
	<td</td>
	<td class="formField"><input type="checkbox" name="availableToPeers" />{translate key="author.submit.availableToPeers"}</td>
</tr>
</table>
</div>

<br />
<table class="form">
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.upload"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/manager/setup'" /></td>
</tr>
</table>

</form>

{include file="common/footer.tpl"}