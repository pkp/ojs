{**
 * metadata_view.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * View (but not edit) metadata of an article.
 *
 * $Id$
 *}
 
{assign var="pageTitle" value="submission.viewMetadata"}
{include file="common/header.tpl"}

<div class="subTitle">{translate key="submission.viewMetadata"}</div>

<br />

<span class="formRequired">{translate key="form.required"}</span>
<br /><br />

<div class="formSectionTitle">{translate key="author.submit.submissionTitle"}</div>
<div class="formSection">

<table class="form">
<tr>
	<td class="formLabel">{translate key="common.title"}:</td>
	<td class="formField">{$title}</td>
</tr>
{if $alternateLocale1}
<tr>
	<td class="formLabel">{translate key="common.title"} ({$languageToggleLocales.$alternateLocale1}):</td>
	<td class="formField">{$titleAlt1}</td>
</tr>
{/if}
{if $alternateLocale2}
<tr>
	<td class="formLabel">{translate key="common.title"} ({$languageToggleLocales.$alternateLocale2}):</td>
	<td class="formField">{$titleAlt2}</td>
</tr>
{/if}
</table>
</div>

<br />

<div class="formSectionTitle">{translate key="author.submit.submissionAbstract"}</div>
<div class="formSection">

<table class="form">
<tr>
	<td class="formLabel">{translate key="common.abstract"}:</td>
	<td class="formField">{$abstract|nl2br}</td>
</tr>
{if $alternateLocale1}
<tr>
	<td class="formLabel">{translate key="common.abstract"} ({$languageToggleLocales.$alternateLocale1}):</td>
	<td class="formField">{$abstractAlt1|nl2br}</td>
</tr>
{/if}
{if $alternateLocale2}
<tr>
	<td class="formLabel">{translate key="common.abstract"} ({$languageToggleLocales.$alternateLocale2}):</td>
	<td class="formField">{$abstractAlt2|nl2br}</td>
</tr>
{/if}
</table>
</div>

<br />

{if $canViewAuthors}

<div class="formSectionTitle">{translate key="author.submit.submissionAuthors"}</div>
<div class="formSection">
{foreach name=authors from=$authors key=authorIndex item=author}
<table class="form">
<tr>
	<td class="formLabel">{translate key="user.firstName"}:</td>
	<td class="formField">{$author.firstName}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="user.middleName"}:</td>
	<td class="formField">{$author.middleName}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="user.lastName"}:</td>
	<td class="formField">{$author.lastName}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="user.affiliation"}:</td>
	<td class="formField">{$author.affiliation}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="user.email"}:</td>
	<td class="formField">{$author.email}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="user.biography"}:</td>
	<td class="formField">{$author.biography|nl2br}</td>
</tr>
</table>
{/foreach}
</div>

<br />

{/if}

<div class="formSectionTitle">{translate key="author.submit.submissionIndexing"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="author.submit.submissionIndexingDescription"}</div>
<table class="form">
{if $journalSettings.metaDiscipline}
<tr>
	<td class="formSubLabel">{translate key="article.discipline"}</td>
	</td></td>
</tr>
<tr>
	<td></td>
	<td class="formField">{$discipline}</td>
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
	<td class="formSubLabel"><a href="submit/{$journalSettings.metaSubjectClassUrl}" target="_blank">{$journalSettings.metaSubjectClassTitle}</a></td>
	<td></td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.subjectClassification"}:</td>
	<td class="formField">{$subjectClass}</td>
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
	<td class="formSubLabel">{translate key="article.subject"}</td>
	</td></td>
</tr>
<tr>
	<td></td>
	<td class="formField">{$subject}</td>
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
	<td class="formLabel">{translate key="article.coverageGeo"}:</td>
	<td class="formField">{$coverageGeo}</td>
</tr>
{if $journalSettings.metaCoverageGeoExamples}
<tr>
	<td></td>
	<td class="formInstructions">{$journalSettings.metaCoverageGeoExamples}</td>
</tr>
{/if}
<tr>
	<td class="formLabel">{translate key="article.coverageChron"}:</td>
	<td class="formField">{$coverageChron}</td>
</tr>
{if $journalSettings.metaCoverageChronExamples}
<tr>
	<td></td>
	<td class="formInstructions">{$journalSettings.metaCoverageChronExamples}</td>
</tr>
{/if}
<tr>
	<td class="formLabel">{translate key="article.coverageSample"}:</td>
	<td class="formField">{$coverageSample}</td>
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
	<td class="formSubLabel">{translate key="article.type"}</td>
	</td></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="author.submit.typeInstructions"}</td>
</tr>
<tr>
	<td></td>
	<td class="formField">{$type}</td>
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
	<td class="formSubLabel">{translate key="article.language"}</td>
	</td></td>
</tr>
<tr>
	<td></td>
	<td class="formField">{$language}</td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="author.submit.languageInstructions"}</td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">{translate key="author.submit.submissionSupportingAgencies"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="author.submit.submissionSupportingAgenciesDescription"}</div>

<table class="form">
<tr>
	<td class="formLabel">{translate key="author.submit.agencies"}:</td>
	<td class="formField">{$sponsor}</td>
</tr>
</table>
</div>

<br />

{include file="common/footer.tpl"}