{**
 * step3.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 3 of journal setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.setup.journalSetup"}
{include file="common/header.tpl"}

<div><a href="{$pageUrl}/manager/setup/2">&lt;&lt; {translate key="manager.setup.previousStep"}</a> | <a href="{$pageUrl}/manager/setup/4">{translate key="manager.setup.nextStep"} &gt;&gt;</a></div>

<br />

<div class="subTitle">{translate key="manager.setup.stepNumber" step=3}: {translate key="manager.setup.guidingSubmissions"}</div>

<br />

<form method="post" action="{$pageUrl}/manager/saveSetup/3">
{include file="common/formErrors.tpl"}

<div class="formSectionTitle">3.1 {translate key="manager.setup.authorGuidelines"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.authorGuidelinesDescription"}</div>

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="authorGuidelines"}{translate key="manager.setup.submissionGuidelines"}:{/formLabel}</td>
	<td class="formField"><textarea name="authorGuidelines" rows="12" cols="60" class="textArea">{$authorGuidelines|escape}</textarea></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="manager.setup.htmlSetupInstructions"}</td>
</tr>
</table>

<br />

<div class="formSubSectionTitle">{translate key="manager.setup.submissionPreparationChecklist"}</div>
<div class="formSectionDesc">{translate key="manager.setup.submissionPreparationChecklistDescription"}</div>

<table class="form">
<tr>
	<td class="formFieldLeft"><b>{translate key="common.order"}</b></td>
	<td></td>
</tr>
</table>

{foreach name=checklist from=$submissionChecklist key=checklistId item=checklistItem}
<table class="form">
<tr>
	<td class="formFieldLeft"><input type="text" name="submissionChecklist[{$checklistId}][order]" value="{$checklistItem.order|escape}" size="3" maxlength="2" class="textField" /></td>
	<td><textarea name="submissionChecklist[{$checklistId}][content]" rows="3" cols="60" class="textArea">{$checklistItem.content|escape}</textarea></td>
</tr>
{if $smarty.foreach.checklist.total > 1}
<tr>
	<td></td>
	<td><input type="submit" name="delChecklist[{$checklistId}]" value="{translate key="common.delete"}" class="formButtonPlain" /></td>
</tr>
{/if}
</table>
{foreachelse}
<table class="form">
<tr>
	<td class="formFieldLeft"><input type="text" name="submissionChecklist[0][order]" value="1" size="3" maxlength="2" class="textField" /></td>
	<td><textarea name="submissionChecklist[0][content]" rows="3" cols="60" class="textArea"></textarea></td>
</tr>
</table>
{/foreach}

<div align="center"><input type="submit" name="addChecklist" value="{translate key="manager.setup.addChecklistItem"}" class="formButtonPlain" /></div>
<br />
</div>

<br />

<div class="formSectionTitle">3.2 {translate key="manager.setup.bibliographicFormat"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.bibliographicFormatDescription"}</div>

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="bibFormat"}{translate key="manager.setup.bibliographicFormat2"}:{/formLabel}</td>
	<td class="formField"><select name="bibFormat" size="1"><option value="apa">APA</option></select></td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">3.3 {translate key="manager.setup.authorCopyrightNotice"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.authorCopyrightNoticeDescription"}</div>

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="copyrightNotice"}{translate key="manager.setup.copyrightNotice"}:{/formLabel}</td>
	<td class="formField"><textarea name="copyrightNotice" rows="12" cols="60" class="textArea">{$copyrightNotice|escape}</textarea></td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">3.4 {translate key="manager.setup.forAuthorsToIndexTheirWork"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.forAuthorsToIndexTheirWorkDescription"}</div>

<table class="form">
<tr valign="top">
	<td class="formFieldLeft"><input type="checkbox" name="metaDiscipline" value="1"{if $metaDiscipline} checked="checked"{/if} /></td>
	<td><b>{translate key="manager.setup.discipline"}</b><br />{translate key="manager.setup.disciplineDescription"}</td>
</tr>
<tr>
	<td></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.disciplineProvideExamples"}:<br />
	<input type="text" name="metaDisciplineExamples" value="{$metaDisciplineExamples|escape}" size="65" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="manager.setup.disciplineExamples"}</td>
</tr>

<tr>
	<td colspan="2">&nbsp;</td>
</tr>

<tr valign="top">
	<td class="formFieldLeft"><input type="checkbox" name="metaSubjectClass" value="1"{if $metaSubjectClass} checked="checked"{/if} /></td>
	<td><b>{translate key="manager.setup.subjectClassification"}</b><br /></td>
</tr>
<tr>
	<td class="formLabelPlain">{translate key="common.title"}:</td>
	<td class="formField"><input type="text" name="metaSubjectClassTitle" value="{$metaSubjectClassTitle|escape}" size="40" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td class="formLabelPlain">{translate key="common.url"}:</td>
	<td class="formField"><input type="text" name="metaSubjectClassUrl" value="{if $metaSubjectClassUrl}{$metaSubjectClassUrl|escape}{else}http://{/if}" size="40" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="manager.setup.subjectClassificationExamples"}</td>
</tr>

<tr>
	<td colspan="2">&nbsp;</td>
</tr>

<tr valign="top">
	<td class="formFieldLeft"><input type="checkbox" name="metaSubject" value="1"{if $metaSubject} checked="checked"{/if} /></td>
	<td><b>{translate key="manager.setup.subjectKeywordTopic"}</b></td>
</tr>
<tr>
	<td></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.subjectProvideExamples"}:<br />
	<input type="text" name="metaSubjectExamples" value="{$metaSubjectExamples|escape}" size="65" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="manager.setup.subjectExamples"}</td>
</tr>

<tr>
	<td colspan="2">&nbsp;</td>
</tr>

<tr valign="top">
	<td class="formFieldLeft"><input type="checkbox" name="metaCoverage" value="1"{if $metaCoverage} checked="checked"{/if} /></td>
	<td><b>{translate key="manager.setup.coverage"}</b><br />{translate key="manager.setup.coverageDescription"}</td>
</tr>
<tr>
	<td></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.coverageGeoProvideExamples"}:<br />
	<input type="text" name="metaCoverageGeoExamples" value="{$metaCoverageGeoExamples|escape}" size="65" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="manager.setup.coverageGeoExamples"}</td>
</tr>
<tr>
	<td></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.coverageChronProvideExamples"}:<br />
	<input type="text" name="metaCoverageChronExamples" value="{$metaCoverageChronExamples|escape}" size="65" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="manager.setup.coverageGeoExamples"}</td>
</tr>
<tr>
	<td></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.coverageResearchSampleProvideExamples"}:<br />
	<input type="text" name="metaCoverageResearchSampleExamples" value="{$metaCoverageResearchSampleExamples|escape}" size="65" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="manager.setup.coverageGeoExamples"}</td>
</tr>

<tr>
	<td colspan="2">&nbsp;</td>
</tr>

<tr valign="top">
	<td class="formFieldLeft"><input type="checkbox" name="metaType" value="1"{if $metaType} checked="checked"{/if} /></td>
	<td><b>{translate key="manager.setup.typeMethodApproach"}</b></td>
</tr>
<tr>
	<td></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.typeProvideExamples"}:<br />
	<input type="text" name="metaTypeExamples" value="{$metaTypeExamples|escape}" size="65" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="manager.setup.typeExamples"}</td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">3.5 {translate key="manager.setup.registerJournalForIndexing"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.registerJournalForIndexingDescription" siteUrl="$pageUrl/" oaiUrl="$pageUrl/oai/"}</div>
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