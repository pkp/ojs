{**
 * step2.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 2 of journal setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.setup.journalSetup"}
{include file="common/header.tpl"}

<div><a href="{$pageUrl}/manager/setup/1">&lt;&lt; {translate key="manager.setup.previousStep"}</a> | <a href="{$pageUrl}/manager/setup/3">{translate key="manager.setup.nextStep"} &gt;&gt;</a></div>

<br />

<div class="subTitle">{translate key="manager.setup.stepNumber" step=2}: {translate key="manager.setup.journalPolicies"}</div>

<br />

<form method="post" action="{$pageUrl}/manager/saveSetup/2">
{include file="common/formErrors.tpl"}

<div class="formSectionTitle">2.1 {translate key="manager.setup.focusAndScopeOfJournal"}</div>
<div class="formSection">
<table class="form">
<tr>
	<td class="formLabel">{formLabel name="focusScopeDesc"}{translate key="manager.setup.focusAndScope"}:{/formLabel}</td>
	<td class="formField"><textarea name="focusScopeDesc" rows="12" cols="60" class="textArea">{$focusScopeDesc|escape}</textarea></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="manager.setup.htmlSetupInstructions"}</td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">2.2 {translate key="manager.setup.peerReviewPolicy"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.peerReviewDescription"}</div>

<table class="form">
<tr>
	<td class="formLabelRight" colspan="2"><span class="formRequired">*</span> {formLabel name="numReviewersPerSubmission"}{translate key="manager.setup.numReviewersPerSubmission"}:{/formLabel} <input type="text" name="numReviewersPerSubmission" value="{$numReviewersPerSubmission|escape}" size="5" maxlength="8" class="textField" /></td>
</tr>
<tr>
	<td class="formLabelRight" colspan="2">{formLabel name="numWeeksPerReview"}{translate key="manager.setup.numWeeksPerReview"}:{/formLabel} <input type="text" name="numWeeksPerReview" value="{$numWeeksPerReview|escape}" size="5" maxlength="8" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="reviewPolicy"}{translate key="manager.setup.peerReviewPolicy2"}:{/formLabel}</td>
	<td class="formField"><textarea name="reviewPolicy" rows="12" cols="60" class="textArea">{$reviewPolicy|escape}</textarea></td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">2.3 {translate key="manager.setup.guidelinesForReviewers"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.guidelinesForReviewersDescription"}:</div>

<table class="form">
<tr>
	<td class="formFieldLeft"><input type="radio" name="mailSubmissionsToReviewers" value="0"{if not $mailSubmissionsToReviewers} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.submissionsToReviewersDescription"}</td>
</tr>

<tr>
	<td class="formFieldLeft"><input type="radio" name="mailSubmissionsToReviewers" value="1"{if $mailSubmissionsToReviewers} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.submissionsToReviewersDescription2"}</td>
</tr>

<tr>
	<td class="formLabel">{formLabel name="reviewGuidelines"}{translate key="manager.setup.guidelines"}:{/formLabel}</td>
	<td class="formField"><textarea name="reviewGuidelines" rows="12" cols="60" class="textArea">{$reviewGuidelines|escape}</textarea></td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">2.4 {translate key="manager.sections"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.sectionsDescription"}</div>

<table class="form">
<tr>
	<td class="formFieldLeft"><input type="radio" name="authorSelectsEditor" value="0"{if not $authorSelectsEditor} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.selectSectionDescription"}</td>
</tr>

<tr>
	<td class="formFieldLeft"><input type="radio" name="authorSelectsEditor" value="1"{if $authorSelectsEditor} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.selectEditorDescription"}</td>
</tr>

<tr>
	<td></td>
	<td class="formInstructions">{translate key="manager.setup.sectionsDefaultSectionDescription"}</td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">2.5 {translate key="manager.setup.privacyStatement"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.privacyStatementDescription"}</div>

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="privacyStatement"}{translate key="manager.setup.privacyStatement2"}:{/formLabel}</td>
	<td class="formField"><textarea name="privacyStatement" rows="12" cols="60" class="textArea">{$privacyStatement|escape}</textarea></td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">2.6 {translate key="manager.setup.openAccessPolicy"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.openAccessPolicyDescription"}</div>

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="openAccessPolicy"}{translate key="manager.setup.openAccessPolicy2"}:{/formLabel}</td>
	<td class="formField"><textarea name="openAccessPolicy" rows="12" cols="60" class="textArea">{$openAccessPolicy|escape}</textarea></td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">2.7 {translate key="manager.setup.addItemtoAboutJournal"}</div>
<div class="formSection">
{foreach from=$aboutJournalItems item=aboutItem}
<input type="hidden" name="aboutItemId[]" value="{$aboutItem[id]}" />
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.aboutItemTitle"}:</td>
	<td class="formField"><input type="text" name="aboutItemTitle[]" value="{$aboutItem[title]|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>
	
<tr>
	<td class="formLabel">{translate key="manager.setup.aboutItemContent"}:</td>
	<td class="formField"><textarea name="aboutItemContent[]" rows="5" cols="40" class="textArea">{$aboutItem[content]|escape}</textarea></td>
</tr>
</table>
{foreachelse}
<input type="hidden" name="aboutItemId[]" value="0" />
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.aboutItemTitle"}:</td>
	<td class="formField"><input type="text" name="aboutItemTitle[]" value="" size="75" maxlength="255" class="textField" /></td>
</tr>
	
<tr>
	<td class="formLabel">{translate key="manager.setup.aboutItemContent"}:</td>
	<td class="formField"><textarea name="aboutItemContent[]" rows="12" cols="60" class="textArea"></textarea></td>
</tr>
</table>
{/foreach}

<div align="center"><input type="submit" class="formButtonPlain" name="addAboutItem" value="{translate key="manager.setup.addAboutItem"}" /></div>
<br />
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