{**
 * step4.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 4 of journal setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.setup.journalSetup"}
{assign var="currentUrl" value="$pageUrl/manager/setup"}
{assign var="pageId" value="manager.setup.step4"}
{include file="common/header.tpl"}

<div><a href="{$pageUrl}/manager/setup/3">&lt;&lt; {translate key="navigation.previousStep"}</a> | <a href="{$pageUrl}/manager/setup/5">{translate key="navigation.nextStep"} &gt;&gt;</a></div>

<br />

<div class="subTitle">{translate key="navigation.stepNumber" step=4}: {translate key="manager.setup.managingTheJournal"}</div>

<br />

<form method="post" action="{$pageUrl}/manager/saveSetup/4">
{include file="common/formErrors.tpl"}

<div class="formSectionTitle">4.1 {translate key="manager.setup.publicationScheduling"}</div>
<div class="formSection">
<div class="formSubSectionTitle">{translate key="manager.setup.publicationFormat"}</div>
<div class="formSectionDesc">{translate key="manager.setup.publicationFormatDescription"}</div>
<table class="form">
<tr>
	<td class="formFieldLeft"><input type="radio" name="publicationFormat" value="1"{if ($publicationFormat == 1)} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.publicationFormatIssue"}</td>
</tr>
<tr>
	<td class="formFieldLeft"><input type="radio" name="publicationFormat" value="2"{if ($publicationFormat == 2)} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.publicationFormatVolume"}</td>
</tr>
<tr>
	<td class="formFieldLeft"><input type="radio" name="publicationFormat" value="3"{if ($publicationFormat == 3)} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.publicationFormatYear"}</td>
</tr>
<tr>
	<td class="formFieldLeft"><input type="radio" name="publicationFormat" value="4"{if ($publicationFormat == 4)} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.publicationFormatTitle"}</td>
</tr>
</table>

<div class="formSubSectionTitle">{translate key="manager.setup.initialIssue"}</div>
<div class="formSectionDesc">{translate key="manager.setup.initialIssueDescription"}</div>
<table class="form">
<tr>
	<td class="formLabel" colspan="2">{formLabel name="initialVolume"}{translate key="issue.volume"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="initialVolume" value="{$initialVolume|escape}" size="5" maxlength="8" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel" colspan="2">{formLabel name="initialNumber"}{translate key="issue.number"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="initialNumber" value="{$initialNumber|escape}" size="5" maxlength="8" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel" colspan="2">{formLabel name="initialYear"}{translate key="issue.year"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="initialYear" value="{$initialYear|escape}" size="5" maxlength="8" class="textField" /></td>
</tr>
<tr>
	<td colspan="2">&nbsp;</td>
</tr>
<tr>
	<td class="formLabel" colspan="2">{formLabel name="issuePerVolume"}{translate key="manager.setup.issuePerVolume"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="issuePerVolume" value="{if $issuePerVolume}{$issuePerVolume|escape}{/if}" size="5" maxlength="8" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel" colspan="2">{formLabel name="volumePerYear"}{translate key="manager.setup.volumePerYear"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="volumePerYear" value="{if $volumePerYear}{$volumePerYear|escape}{/if}" size="5" maxlength="8" class="textField" /></td>
</tr>
</table>

<div class="formSubSectionTitle">{translate key="manager.setup.frequencyOfPublicationPolicy"}</div>
<table class="form">
<tr>
	<td class="formLabel"></td>
	<td class="formField"><textarea name="pubFreqPolicy" rows="12" cols="60" class="textArea">{$pubFreqPolicy|escape}</textarea></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="manager.setup.appearInAboutJournal"}</td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">4.2 {translate key="manager.setup.subscription"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.subscriptionDescription"}</div>
<table class="form">
<tr>
	<td class="formFieldLeft"><input type="checkbox" name="enableSubscriptions" value="1"{if $enableSubscriptions} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.enableSubscriptions"}</td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">4.3 {translate key="manager.setup.publicIdentifier"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.publicIdentifierDescription"}</div>
<table class="form">
<tr>
	<td class="formFieldLeft"><input type="checkbox" name="enablePublicIssueId" value="1"{if $enablePublicIssueId} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.enablePublicIssueId"}</td>
</tr>
<tr>
	<td class="formFieldLeft"><input type="checkbox" name="enablePublicArticleId" value="1"{if $enablePublicArticleId} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.enablePublicArticleId"}</td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">4.4 {translate key="manager.setup.managementOfBasicEditorialSteps"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.basicEditorialStepsDescription"}</div>
<table class="form">
<tr>
	<td class="formFieldLeft"><input type="radio" name="editorialProcessType" value="0"{if not $editorialProcessType} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.editorialProcess1"}</td>
</tr>
<tr>
	<td class="formFieldLeft"><input type="radio" name="editorialProcessType" value="1"{if $editorialProcessType == 1} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.editorialProcess2"}</td>
</tr>
<tr>
	<td class="formFieldLeft"><input type="radio" name="editorialProcessType" value="2"{if $editorialProcessType == 2} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.editorialProcess3"}</td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">4.5 {translate key="manager.setup.copyediting"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.copyeditingDescription"}</div>
<table class="form">
<tr>
	<td class="formFieldLeft"><input type="checkbox" name="useCopyeditors" value="1"{if $useCopyeditors} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.useCopyeditors"}</td>
</tr>
</table>

<div class="formSubSectionTitle">{translate key="manager.setup.copyeditInstructions"}</div>
<div class="formSectionDesc">{translate key="manager.setup.copyeditInstructionsDescription"}</div>
<table class="form">
<tr>
	<td class="formLabel"></td>
	<td class="formField"><textarea name="copyeditInstructions" rows="12" cols="60" class="textArea">{$copyeditInstructions|escape}</textarea></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="manager.setup.htmlSetupInstructions"}</td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">4.6 {translate key="manager.setup.layoutAndGalleys"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.layoutAndGalleysDescription"}</div>
<table class="form">
<tr>
	<td class="formFieldLeft"><input type="checkbox" name="useLayoutEditors" value="1"{if $useLayoutEditors} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.useLayoutEditors"}</td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">4.7 {translate key="manager.setup.proofreading"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.proofreadingDescription"}</div>
<table class="form">
<tr>
	<td class="formFieldLeft"><input type="checkbox" name="useProofreaders" value="1"{if $useProofreaders} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.useProofreaders"}</td>
</tr>
</table>

<div class="formSubSectionTitle">{translate key="manager.setup.proofingInstructions"}</div>
<div class="formSectionDesc">{translate key="manager.setup.proofingInstructionsDescription"}</div>
<table class="form">
<tr>
	<td class="formLabel"></td>
	<td class="formField"><textarea name="proofInstructions" rows="12" cols="60" class="textArea">{$proofInstructions|escape}</textarea></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="manager.setup.htmlSetupInstructions"}</td>
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
