{**
 * step2.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 2 of author submit.
 *
 * $Id$
 *}

{assign var="pageTitle" value="author.submit"}
{include file="common/header.tpl"}

<div><a href="{$pageUrl}/author/submit/1">&lt;&lt; {translate key="manager.setup.previousStep"}</a> | <a href="{$pageUrl}/author/submit/3">{translate key="manager.setup.nextStep"} &gt;&gt;</a></div>

<br />

<div class="subTitle">{translate key="manager.setup.stepNumber" step=2}: {translate key="author.submit.metadata"}</div>

<br />
<div> {translate key="author.submit.metadataDescription"} </div>
<br />
<div> {translate key="author.submit.privacyStatement"} </div>
<br />
<div> {translate key="author.submit.privacyStatementFull"} </div>
<br />
<span class="formRequired">(* {translate key="common.required"})</span>

<form method="post" action="{$pageUrl}/author/saveSetup/2">
{include file="common/formErrors.tpl"}

<div class="formSectionTitle">2.1 {translate key="author.submit.submissionAuthors"}</div>
<div class="formSection">
<table class="form">
<tr>
	<td class="formLabel">*{formLabel name="firstName"}{translate key="common.firstName"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="firstName" size="30" value="{$firstName|escape}" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="middleName"}{translate key="common.middleName"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="middleName" size="30" value="{$middleName|escape}"class="textField"  /></td>
</tr>
<tr>
	<td class="formLabel">*{formLabel name="lastName"}{translate key="common.lastName"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="lastName" size="30" value="{$lastName|escape}" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">*{formLabel name="email"}{translate key="common.email"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="email" size="30" value="{$email|escape}" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="affiliation"}{translate key="common.affiliation"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="affiliation" size="60" value="{$affiliation|escape}" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="biographicalStatement"}{translate key="common.biographicalStatement"}:{/formLabel}</td>
	<td class="formField"><textarea name="biographicalStatement" rows="12" cols="60" class="textArea">{$biographicalStatement|escape}</textarea></td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">2.2 {translate key="author.submit.submissionTitle"}</div>
<div class="formSection">

<table class="form">

<tr>
	<td class="formLabel">*{formLabel name="title"}{translate key="common.title"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="title" size="70" value="{$title|escape}" class="textField" /></td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">2.3 {translate key="author.submit.submissionAbstract"}</div>
<div class="formSection">

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="abstract"}{translate key="common.abstract"}:{/formLabel}</td>
	<td class="formField"><textarea name="abstract" rows="12" cols="60" class="textArea">{$abstract|escape}</textarea></td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">2.4 {translate key="author.submit.submissionIndexing"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="author.submit.submissionIndexingDescription"}</div>

</div>

<br />

<div class="formSectionTitle">2.5 {translate key="author.submit.submissionSupportingAgencies"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="author.submit.submissionSupportingAgenciesDescription"}</div>

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="agencies"}{translate key="author.submit.agencies"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="agencies" size="70" value="{$agencies|escape}" class="textField" /></td>
</tr>
</table>
</div>

<br />

<table class="form">
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.save"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/manager/setup'" /></td>
</tr>
</table>

</form>

{include file="common/footer.tpl"}