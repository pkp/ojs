{**
 * step2.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 2 of author article submission.
 *
 * $Id$
 *}

{include file="author/submit/submitHeader.tpl"}

<div class="subTitle">{translate key="author.submit.stepNumber" step=2}: {translate key="author.submit.metadata"}</div>

<br />

{translate key="author.submit.metadataDescription"}<br /><br />

<div class="sectionTitle">{translate key="author.submit.privacyStatement"}</div>
<br />
{$journalSettings.privacyStatement|nl2br}<br /><br />

<form name="submit" method="post" action="{$pageUrl}/author/saveSubmit/{$submitStep}">
<input type="hidden" name="articleId" value="{$articleId}" />
{include file="common/formErrors.tpl"}

<span class="formRequired">{translate key="form.required"}</span>
<br /><br />

{literal}
<script type="text/javascript">
// Move author up/down
function moveAuthor(dir, authorIndex) {
	var form = document.submit;
	form.moveAuthor.value = 1;
	form.moveAuthorDir.value = dir;
	form.moveAuthorIndex.value = authorIndex;
	form.submit();
}
</script>
{/literal}

<div class="formSectionTitle">2.1 {translate key="author.submit.submissionAuthors"}</div>
<div class="formSection">
<input type="hidden" name="deletedAuthors" value="{$deletedAuthors|escape}" />
<input type="hidden" name="moveAuthor" value="0" />
<input type="hidden" name="moveAuthorDir" value="" />
<input type="hidden" name="moveAuthorIndex" value="" />

{foreach name=authors from=$authors key=authorIndex item=author}
<input type="hidden" name="authors[{$authorIndex}][authorId]" value="{$author.authorId|escape}" />
<input type="hidden" name="authors[{$authorIndex}][seq]" value="{$authorIndex+1}" />
{if $smarty.foreach.authors.total <= 1}
<input type="hidden" name="primaryContact" value="{$authorIndex}" />
{/if}

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="authors[$authorIndex][firstName]" required="true"}{translate key="user.firstName"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="authors[{$authorIndex}][firstName]" value="{$author.firstName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="authors[{$authorIndex][middleName]"}{translate key="user.middleName"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="authors[{$authorIndex}][middleName]" value="{$author.middleName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="authors[$authorIndex][lastName]" required="true"}{translate key="user.lastName"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="authors[{$authorIndex}][lastName]" value="{$author.lastName|escape}" size="20" maxlength="90" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="authors[$authorIndex][affiliation]"}{translate key="user.affiliation"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="authors[{$authorIndex}][affiliation]" value="{$author.affiliation|escape}" size="30" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="authors[$authorIndex][email]" required="true"}{translate key="user.email"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="authors[{$authorIndex}][email]" value="{$author.email|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="authors[$authorIndex][biography]"}{translate key="user.biography"}:{/formLabel}</td>
	<td class="formField"><textarea name="authors[{$authorIndex}][biography]" rows="5" cols="40" class="textArea">{$author.biography|escape}</textarea></td>
</tr>
{if $smarty.foreach.authors.total > 1}
<tr>
	<td class="formFieldLeft"><input type="radio" name="primaryContact" value="{$authorIndex}"{if $primaryContact == $authorIndex} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="author.submit.selectPrincipalContact"}</td>
</tr>
<tr>
	<td></td>
	<td class="formField"><a href="javascript:moveAuthor('u', '{$authorIndex}')">&uarr;</a> <a href="javascript:moveAuthor('d', '{$authorIndex}')">&darr;</a> <input type="submit" name="delAuthor[{$authorIndex}]" value="{translate key="common.delete"}" class="formButtonPlain" /></td>
</tr>
{/if}
</table>
{foreachelse}
<input type="hidden" name="authors[0][authorId]" value="0" />
<input type="hidden" name="primaryContact" value="0" />
<input type="hidden" name="authors[0][seq]" value="1" />
<table class="form">
<tr>
	<td class="formLabel">{formLabel name="authors[0][firstName]" required="true"}{translate key="user.firstName"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="authors[0][firstName]" size="20" maxlength="40" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="authors[0][middleName]"}{translate key="user.middleName"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="authors[0][middleName]" size="20" maxlength="40" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="authors[0][lastName]" required="true"}{translate key="user.lastName"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="authors[0][lastName]" size="20" maxlength="90" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="authors[0][affiliation]"}{translate key="user.affiliation"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="authors[0][affiliation]" size="30" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="authors[0][email]" required="true"}{translate key="user.email"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="authors[0][email]" size="30" maxlength="90" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="authors[0][biography]"}{translate key="user.biography"}:{/formLabel}</td>
	<td class="formField"><textarea name="authors[0][biography]" rows="5" cols="40" class="textArea"></textarea></td>
</tr>
</table>
{/foreach}

<div align="center"><input type="submit" class="formButtonPlain" name="addAuthor" value="{translate key="author.submit.addAuthor"}" /></div>
<br />
</div>

<br />

<div class="formSectionTitle">2.2 {translate key="author.submit.submissionTitle"}</div>
<div class="formSection">

<table class="form">

<tr>
	<td class="formLabel">{formLabel name="title" required="true"}{translate key="common.title"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="title" value="{$title|escape}" size="75" maxlength="255" class="textField" /></td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">2.3 {translate key="author.submit.submissionAbstract"}</div>
<div class="formSection">

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="abstract"}{translate key="common.abstract"}:{/formLabel}</td>
	<td class="formField"><textarea name="abstract" rows="15" cols="75" class="textArea">{$abstract|escape}</textarea></td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">2.4 {translate key="author.submit.submissionIndexing"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="author.submit.submissionIndexingDescription"}</div>
<table class="form">
{if $journalSettings.metaDiscipline}
<tr>
	<td class="formSubLabel">{formLabel name="discipline"}{translate key="article.discipline"}{/formLabel}</td>
	</td></td>
</tr>
<tr>
	<td></td>
	<td class="formField"><input type="text" name="discipline" value="{$discipline|escape}" size="60" maxlength="255" class="textField" /></td>
</tr>
{if $journalSettings.metaDisciplineExamples}
<tr>
	<td></td>
	<td class="formInstructions">{$journalSettings.metaDisciplineExamples}</td>
</tr>
{/if}
<tr>
	<td>&nbsp;</td>
	<td></td>
</tr>
{/if}

{if $journalSettings.metaSubjectClass}
<tr>
	<td class="formSubLabel"><a href="{$journalSettings.metaSubjectClassUrl}" target="_blank">{$journalSettings.metaSubjectClassTitle}</a></td>
	<td></td>
<tr>
	<td class="formLabel">{formLabel name="subjectClass"}{translate key="article.subjectClassification"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="subjectClass" value="{$subjectClass|escape}" size="60" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="author.submit.subjectClassInstructions"}</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td></td>
</tr>
{/if}

{if $journalSettings.metaSubject}
<tr>
	<td class="formSubLabel">{formLabel name="subject"}{translate key="article.subject"}{/formLabel}</td>
	</td></td>
</tr>
<tr>
	<td></td>
	<td class="formField"><input type="text" name="subject" value="{$subject|escape}" size="60" maxlength="255" class="textField" /></td>
</tr>
{if $journalSettings.metaSubjectExamples}
<tr>
	<td></td>
	<td class="formInstructions">{$journalSettings.metaSubjectExamples}</td>
</tr>
{/if}
<tr>
	<td>&nbsp;</td>
	<td></td>
</tr>
{/if}

{if $journalSettings.metaCoverage}
<tr>
	<td class="formSubLabel">{translate key="article.coverage"}</td>
	<td></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="author.submit.coverageInstructions"}</td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="coverageGeo"}{translate key="article.coverageGeo"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="coverageGeo" value="{$coverageGeo|escape}" size="60" maxlength="255" class="textField" /></td>
</tr>
{if $journalSettings.metaCoverageGeoExamples}
<tr>
	<td></td>
	<td class="formInstructions">{$journalSettings.metaCoverageGeoExamples}</td>
</tr>
{/if}
<tr>
	<td class="formLabel">{formLabel name="coverageChron"}{translate key="article.coverageChron"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="coverageChron" value="{$coverageChron|escape}" size="60" maxlength="255" class="textField" /></td>
</tr>
{if $journalSettings.metaCoverageChronExamples}
<tr>
	<td></td>
	<td class="formInstructions">{$journalSettings.metaCoverageChronExamples}</td>
</tr>
{/if}
<tr>
	<td class="formLabel">{formLabel name="coverageSample"}{translate key="article.coverageSample"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="coverageSample" value="{$coverageSample|escape}" size="60" maxlength="255" class="textField" /></td>
</tr>
{if $journalSettings.metaCoverageResearchSampleExamples}
<tr>
	<td></td>
	<td class="formInstructions">{$journalSettings.metaCoverageResearchSampleExamples}</td>
</tr>
{/if}
<tr>
	<td>&nbsp;</td>
	<td></td>
</tr>
{/if}

{if $journalSettings.metaType}
<tr>
	<td class="formSubLabel">{formLabel name="type"}{translate key="article.type"}{/formLabel}</td>
	</td></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="author.submit.typeInstructions"}</td>
</tr>
<tr>
	<td></td>
	<td class="formField"><input type="text" name="type" value="{$type|escape}" size="60" maxlength="255" class="textField" /></td>
</tr>
{if $journalSettings.metaTypeExamples}
<tr>
	<td></td>
	<td class="formInstructions">{$journalSettings.metaTypeExamples}</td>
</tr>
{/if}
<tr>
	<td>&nbsp;</td>
	<td></td>
</tr>
{/if}

<tr>
	<td class="formSubLabel">{formLabel name="language"}{translate key="article.language"}{/formLabel}</td>
	</td></td>
</tr>
<tr>
	<td></td>
	<td class="formField"><input type="text" name="language" value="{$language|escape}" size="5" maxlength="10" class="textField" /></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="author.submit.languageInstructions"}</td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">2.5 {translate key="author.submit.submissionSupportingAgencies"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="author.submit.submissionSupportingAgenciesDescription"}</div>

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="sponsor"}{translate key="author.submit.agencies"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="sponsor" value="{$sponsor|escape}" size="75" maxlength="255" class="textField" /></td>
</tr>
</table>
</div>

<br />

<table class="form">
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.continue"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="confirmAction('{$pageUrl}/author', '{translate|escape:"javascript" key="author.submit.cancelSubmission"}')" /></td>
</tr>
</table>

</form>

{include file="common/footer.tpl"}