{**
 * step1.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 1 of journal setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.setup.journalSetup"}
{assign var="currentUrl" value="$pageUrl/manager/setup"}
{include file="common/header.tpl"}

<div><span class="disabledText">&lt;&lt; {translate key="navigation.previousStep"}</span> | <a href="{$pageUrl}/manager/setup/2">{translate key="navigation.nextStep"} &gt;&gt;</a></div>

<br />

<div class="subTitle">{translate key="navigation.stepNumber" step=1}: {translate key="manager.setup.gettingDownTheDetails"}</div>

<form method="post" action="{$pageUrl}/manager/saveSetup/1">
{include file="common/formErrors.tpl"}

<span class="formRequired">{translate key="form.required"}</span>
<br /><br />

<div class="formSectionTitle">1.1 {translate key="manager.setup.generalInformation"}</div>
<div class="formSection">
<table class="form">
<tr>	
	<td class="formLabel">{formLabel name="journalTitle" required="true"}{translate key="manager.setup.journalTitle"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="journalTitle" value="{$journalTitle|escape}" size="40" maxlength="120" class="textField" /></td>
</tr>
	
<tr>
	<td class="formLabel">{formLabel name="journalInitials" required="true"}{translate key="manager.setup.journalInitials"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="journalInitials" value="{$journalInitials|escape}" size="8" maxlength="16" class="textField" /></td>
</tr>
	
<tr>
	<td class="formLabel">{formLabel name="issn"}{translate key="manager.setup.issn"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="issn" value="{$issn|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="manager.setup.issnDescription"}</td>
</tr>
	
<tr>
	<td class="formLabel">{formLabel name="mailingAddress"}{translate key="common.mailingAddress"}:{/formLabel}</td>
	<td class="formField"><textarea name="mailingAddress" rows="3" cols="40" class="textArea">{$mailingAddress|escape}</textarea></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="manager.setup.mailingAddressDescription"}</td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">1.2 {translate key="manager.setup.sectionsAndSectionEditors"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.sectionsDescription"}</div>
</div>

<br />

<div class="formSectionTitle">1.3 {translate key="manager.setup.editorialReviewBoard"}</div>
<div class="formSection">
<table class="form">
<tr>
	<td class="formFieldLeft"><input type="checkbox" name="useEditorialBoard" value="1"{if $useEditorialBoard} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.useEditorialReviewBoard"}</td>
</tr>
</table>

<div class="formSectionDesc">{translate key="manager.setup.editorialReviewBoardDescription"}</div>
</div>

<br />

<div class="formSectionTitle">1.4 {translate key="manager.setup.principalContact"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.principalContactDescription"}</div>

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="contactName"}{translate key="user.name"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="contactName" value="{$contactName|escape}" size="20" maxlength="60" class="textField" /></td>
</tr>
	
<tr>
	<td class="formLabel">{formLabel name="contactTitle"}{translate key="user.title"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="contactTitle" value="{$contactTitle|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>
	
<tr>
	<td class="formLabel">{formLabel name="contactAffiliation"}{translate key="user.affiliation"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="contactAffiliation" value="{$contactAffiliation|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>
	
<tr>
	<td class="formLabel">{formLabel name="contactEmail"}{translate key="user.email"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="contactEmail" value="{$contactEmail|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>
	
<tr>
	<td class="formLabel">{formLabel name="contactPhone"}{translate key="user.phone"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="contactPhone" value="{$contactPhone|escape}" size="15" maxlength="24" class="textField" /></td>
</tr>
	
<tr>
	<td class="formLabel">{formLabel name="contactFax"}{translate key="user.fax"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="contactFax" value="{$contactFax|escape}" size="15" maxlength="24" class="textField" /></td>
</tr>
	
<tr>
	<td class="formLabel">{formLabel name="contactMailingAddress"}{translate key="common.mailingAddress"}:{/formLabel}</td>
	<td class="formField"><textarea name="contactMailingAddress" rows="3" cols="40" class="textArea">{$contactMailingAddress|escape}</textarea></td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">1.5 {translate key="manager.setup.technicalSupportContact"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.technicalSupportContactDescription"}</div>

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="supportName"}{translate key="user.name"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="supportName" value="{$supportName|escape}" size="20" maxlength="60" class="textField" /></td>
</tr>
	
<tr>
	<td class="formLabel">{formLabel name="supportEmail"}{translate key="user.email"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="supportEmail" value="{$supportEmail|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>
	
<tr>
	<td class="formLabel">{formLabel name="supportPhone"}{translate key="user.phone"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="supportPhone" value="{$supportPhone|escape}" size="15" maxlength="24" class="textField" /></td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">1.6 {translate key="manager.setup.sponsors"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.sponsorsDescription"}</div>

<table class="form">
	<td class="formLabel">{formLabel name="sponsorNote"}{translate key="manager.setup.note"}:{/formLabel}</td>
	<td class="formField"><textarea name="sponsorNote" rows="5" cols="40" class="textArea">{$sponsorNote|escape}</textarea></td>
</table>

{foreach name=sponsors from=$sponsors key=sponsorId item=sponsor}
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.institution"}:</td>
	<td class="formField"><input type="text" name="sponsors[{$sponsorId}][institution]" value="{$sponsor.institution|escape}" size="30" maxlength="90" class="textField" />{if $smarty.foreach.sponsors.total > 1}<input type="submit" name="delSponsor[{$sponsorId}]" value="{translate key="common.delete"}" class="formButtonPlain" />{/if}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="common.url"}:</td>
	<td class="formField"><input type="text" name="sponsors[{$sponsorId}][url]" value="{$sponsor.url|escape}" size="45" maxlength="255" class="textField" /></td>
</tr>
</table>
{foreachelse}
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.institution"}:</td>
	<td class="formField"><input type="text" name="sponsors[0][institution]" size="30" maxlength="90" class="textField" /></td>
</tr>
	
<tr>
	<td class="formLabel">{translate key="common.url"}:</td>
	<td class="formField"><input type="text" name="sponsors[0][url]" size="45" maxlength="255" class="textField" /></td>
</tr>
</table>
{/foreach}

<div align="center"><input type="submit" name="addSponsor" value="{translate key="manager.setup.addSponsor"}" class="formButtonPlain" /></div>
<br />
</div>

<br />

<div class="formSectionTitle">1.7 {translate key="manager.setup.contributors"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.contributorsDescription"}</div>

<table class="form">
	<td class="formLabel">{formLabel name="contributorNote"}{translate key="manager.setup.note"}:{/formLabel}</td>
	<td class="formField"><textarea name="contributorNote" rows="5" cols="40" class="textArea">{$contributorNote|escape}</textarea></td>
</table>

{foreach name=contributors from=$contributors key=contributorId item=contributor}
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.contributor"}:</td>
	<td class="formField"><input type="text" name="contributors[{$contributorId}][name]" value="{$contributor.name|escape}" size="30" maxlength="90" class="textField" />{if $smarty.foreach.contributors.total>1}<input type="submit" name="delContributor[{$contributorId}]" value="{translate key="common.delete"}" class="formButtonPlain" />{/if}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="common.url"}:</td>
	<td class="formField"><input type="text" name="contributors[{$contributorId}][url]" value="{$contributor.url|escape}" size="45" maxlength="255" class="textField" /></td>
</tr>
</table>
{foreachelse}
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.contributor"}:</td>
	<td class="formField"><input type="text" name="contributors[0][name]" size="30" maxlength="90" class="textField" /></td>
</tr>
	
<tr>
	<td class="formLabel">{translate key="common.url"}:</td>
	<td class="formField"><input type="text" name="contributors[0][url]" value="" size="45" maxlength="255" class="textField" /></td>
</tr>
</table>
{/foreach}

<div align="center"><input type="submit" name="addContributor" value="{translate key="manager.setup.addContributor"}" class="formButtonPlain" /></div>
<br />
</div>

<br />

<div class="formSectionTitle">1.8 {translate key="manager.setup.searchEngineIndexing"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.searchEngineIndexingDescription"}</div>
<table class="form">
<tr>
	<td class="formLabel">{formLabel name="searchDescription"}{translate key="common.description"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="searchDescription" value="{$searchDescription|escape}" size="40" maxlength="255" class="textField" /></td>
</tr>
	
<tr>
	<td class="formLabel">{formLabel name="searchKeywords"}{translate key="common.keywords"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="searchKeywords" value="{$searchKeywords|escape}" size="40" maxlength="255" class="textField" /></td>
</tr>
	
<tr>
	<td class="formLabel">{formLabel name="customHeaders"}{translate key="manager.setup.customTags"}:{/formLabel}</td>
	<td class="formField"><textarea name="customHeaders" rows="3" cols="60" class="textArea">{$customHeaders|escape}</textarea></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="manager.setup.customTagsDescription"}</td>
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
