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

<div class="formSectionDesc"><b>{translate key="manager.setup.submissionPreparationChecklist"}</b>
<br /><br />
{translate key="manager.setup.submissionPreparationChecklistDescription"}
</div>

<table class="form">
<tr>
	<td class="formFieldLeft"><b>{translate key="common.order"}</b></td>
	<td></td>
</tr>
</table>

{foreach from=$submissionChecklist item=checklistItem}
<input type="hidden" name="checklistItemId[]" value="{$checklistItem[id]}" />
<table class="form">
<tr>
	<td class="formFieldLeft"><input type="text" name="checklistItemOrder[]" value="{$checklistItem[order]|escape}" size="3" maxlength="2" class="textField" /></td>
	<td><textarea name="checklistItemContent[]" rows="3" cols="60" class="textArea">{$checklistItem[content]|escape}</textarea></td>
	<td><a href=\"\">{translate key="common.delete"}</a></td>
</tr>
</table>
{foreachelse}
<input type="hidden" name="checklistItemId[]" value="0" />
<table class="form">
<tr>
	<td class="formFieldLeft"><input type="text" name="checklistItemOrder[]" value="1" size="3" maxlength="2" class="textField" /></td>
	<td><textarea name="checklistItemContent[]" rows="3" cols="60" class="textArea"></textarea></td>
	<td></td>
</tr>
</table>
{/foreach}

<div align="center"><input type="submit" class="formButtonPlain" name="addChecklistItem" value="{translate key="manager.setup.addChecklistItem"}" /></div>
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

<input type="checkbox" name="enableMetaDiscipline" value="1"{if $enableMetaDiscipline} checked="checked"{/if} /> <b>{translate key="manager.setup.discipline"}</b><br />
{translate key="manager.setup.disciplineDescription"}:<br />
<input type="text" name="chDisciplineExamples" value="Philosophy; Political Science; Law; Economics " size="65" /><br />

<span class="instructions_red">{translate key="manager.setup.disciplineExamples"}</span><br />
<br /><br />

<input type="checkbox" name="bMetaSubjectClass" value="1" /> <b>{translate key="manager.setup.subjectClassification"}</b><br />
<span class="halfline"><br /></span>
{translate key="manager.setup.subjectClassificationDescription"}:<br /><input type="text" name="chSubjectClassTitle" value="abc" size="40" maxlength="255" /><br />

{translate key="common.url"}: <input type="text" name="chSubjectClassURL" value="http://" size="65" maxlength="255" /><br />
<span class="instructions_red">{translate key="manager.setup.subjectClassificationExamples"}</span><br />
<br /><br />	

<input type="checkbox" name="bMetaSubject" value="1" checked="checked" /> <b>{translate key="manager.setup.subjectKeywordTopic"}</b><br />
{translate key="manager.setup.subjectKeywordTopicDescription"}:<br />
<input type="text" name="chSubjectExamples" value="Political economy; Publishing models; Democratic theory" size="65" /><br />

<span class="instructions_red">{translate key="manager.setup.subjectKeywordTopicExamples"}</span><br />

<br /><br />
<input type="checkbox" name="bMetaCoverage" value="1" checked="checked" /> <b>{translate key="manager.setup.coverage"}</b><br />
{translate key="manager.setup.coverageDescription"}<br /><br />
{translate key="manager.setup.coverageGeo"}:<br />
<input type="text" name="chCovGeoExamples" value="Western; Continental; American; Mid-Western" size="65" /><br />
<span class="instructions_red">{translate key="manager.setup.coverageGeoExamples"}</span><br />
<br />
{translate key="manager.setup.coverageChron"}:<br />
<input type="text" name="chCovChronExamples" value="Twenieth Century; Enlightenment; 1950s" size="65" /><br />
<span class="instructions_red">{translate key="manager.setup.coverageChronExamples"}</span><br />
<br />
{translate key="manager.setup.coverageResearchSample"}:<br />
<input type="text" name="chCovSampleExamples" value="Young children; Male adults; Whole numbers; " size="65" /><br />
<span class="instructions_red">{translate key="manager.setup.coverageResearchSampleExamples"}</span><br />


<br /><br />

<input type="checkbox" name="bMetaType" value="1" checked="checked" /> <b>{translate key="manager.setup.typeMethodApproach"}</b><br />
{translate key="manager.setup.typeMethodApproachDescription"}:<br />
<input type="text" name="chTypeExamples" value="Concetual analysis; Socratic dialogue; Economic model testing" size="65" /><br />
<span class="instructions_red">{translate key="manager.setup.typeMethodApproachExamples"}</span><br />

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