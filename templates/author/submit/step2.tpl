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

{assign var="pageId" value="author.submit.step2"}
{assign var="pageTitle" value="author.submit.step2"}
{include file="author/submit/submitHeader.tpl"}

<p>{translate key="author.submit.metadataDescription"}</p>

<h3>{translate key="author.submit.privacyStatement"}</h3>
<br />
{$journalSettings.privacyStatement|nl2br}

<div class="separator"></div>

<form name="submit" method="post" action="{$pageUrl}/author/saveSubmit/{$submitStep}">
<input type="hidden" name="articleId" value="{$articleId}" />
{include file="common/formErrors.tpl"}

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

<h3>{translate key="author.submit.submissionAuthors"}</h3>
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

<table width="100%" class="data">
<tr>
	<td class="label">{fieldLabel name="authors[$authorIndex][firstName]" required="true" key="user.firstName"}</td>
	<td class="value"><input type="text" name="authors[{$authorIndex}][firstName]" value="{$author.firstName|escape}" size="20" maxlength="40" /></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="authors[{$authorIndex][middleName]" key="user.middleName"}</td>
	<td class="value"><input type="text" name="authors[{$authorIndex}][middleName]" value="{$author.middleName|escape}" size="20" maxlength="40" /></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="authors[$authorIndex][lastName]" required="true" key="user.lastName"}</td>
	<td class="value"><input type="text" name="authors[{$authorIndex}][lastName]" value="{$author.lastName|escape}" size="20" maxlength="90" /></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="authors[$authorIndex][affiliation]" key="user.affiliation"}</td>
	<td class="value"><input type="text" name="authors[{$authorIndex}][affiliation]" value="{$author.affiliation|escape}" size="30" maxlength="255"/></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="authors[$authorIndex][email]" required="true" key="user.email"}</td>
	<td class="value"><input type="text" name="authors[{$authorIndex}][email]" value="{$author.email|escape}" size="30" maxlength="90" /></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="authors[$authorIndex][biography]" key="user.biography"}</td>
	<td class="value"><textarea name="authors[{$authorIndex}][biography]" rows="5" cols="40">{$author.biography|escape}</textarea></td>
</tr>
{if $smarty.foreach.authors.total > 1}
<tr>
	<td></td>
	<td><a href="javascript:moveAuthor('u', '{$authorIndex}')">&uarr;</a> <a href="javascript:moveAuthor('d', '{$authorIndex}')">&darr;</a> <input type="submit" name="delAuthor[{$authorIndex}]" value="{translate key="common.delete"}" class="button" /></td>
</tr>
<tr>
	<td></td>
	<td class="value" colspan="2"><input type="radio" name="primaryContact" value="{$authorIndex}"{if $primaryContact == $authorIndex} checked="checked"{/if} /> <label for="primaryContact">{translate key="author.submit.selectPrincipalContact"}</label></td>
</tr>
{/if}
</table>
{foreachelse}
<input type="hidden" name="authors[0][authorId]" value="0" />
<input type="hidden" name="primaryContact" value="0" />
<input type="hidden" name="authors[0][seq]" value="1" />
<table width="100%' class="data">
<tr>
	<td class="label">{fieldLabel name="authors[0][firstName]" required="true" key="user.firstName"}</td>
	<td class="value"><input type="text" name="authors[0][firstName]" size="20" maxlength="40" /></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="authors[0][middleName]" key="user.middleName"}</td>
	<td class="value"><input type="text" name="authors[0][middleName]" size="20" maxlength="40" /></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="authors[0][lastName]" required="true" key="user.lastName"}</td>
	<td class="value"><input type="text" name="authors[0][lastName]" size="20" maxlength="90" /></td>
</tr>
<tr>/
	<td class="label">{fieldLabel name="authors[0][affiliation]" key="user.affiliation"}</td>
	<td class="value"><input type="text" name="authors[0][affiliation]" size="30" maxlength="255" /></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="authors[0][email]" required="true" key="user.email"}</td>
	<td class="value"><input type="text" name="authors[0][email]" size="30" maxlength="90" /></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="authors[0][biography]" key="user.biography"}</td>
	<td class="value"><textarea name="authors[0][biography]" rows="5" cols="40"></textarea></td>
</tr>
</table>
{/foreach}

<p><input type="submit" class="button" name="addAuthor" value="{translate key="author.submit.addAuthor"}" /></p>

<div class="separator"></div>

<h3>{translate key="submission.titleAndAbstract"}</h3>

<table width="100%" class="data">

<tr>
	<td class="label">{fieldLabel name="title" required="true" key="article.title"}</td>
	<td class="value"><input type="text" name="title" value="{$title|escape}" size="75" maxlength="255" /></td>
</tr>
{if $alternateLocale1}
<tr>
	<td class="label">{fieldLabel name="titleAlt1" key="article.title"} ({$languageToggleLocales.$alternateLocale1})</td>
	<td class="value"><input type="text" name="titleAlt1" value="{$titleAlt1|escape}" size="75" maxlength="255" /></td>
</tr>
{/if}
{if $alternateLocale2}
<tr>
	<td class="label">{fieldLabel name="titleAlt2" key="article.title"} ({$languageToggleLocales.$alternateLocale2})</td>
	<td class="value"><input type="text" name="titleAlt2" value="{$titleAlt2|escape}" size="75" maxlength="255" /></td>
</tr>
{/if}

<tr>
	<td class="label">{fieldLabel name="abstract" key="article.abstract"}</td>
	<td class="value"><textarea name="abstract" rows="15" cols="75">{$abstract|escape}</textarea></td>
</tr>
{if $alternateLocale1}
<tr>
	<td class="label">{fieldLabel name="abstractAlt1" key="article.abstract"} ({$languageToggleLocales.$alternateLocale1})</td>
	<td class="value"><textarea name="abstractAlt1" rows="15" cols="75">{$abstractAlt1|escape}</textarea></td>
</tr>
{/if}
{if $alternateLocale2}
<tr>
	<td class="label">{fieldLabel name="abstractAlt2" key="article.abstract"} ({$languageToggleLocales.$alternateLocale2})</td>
	<td class="value"><textarea name="abstractAlt2" rows="15" cols="75">{$abstractAlt2|escape}</textarea></td>
</tr>
{/if}
</table>

<div class="separator"></div>

<h3>{translate key="author.submit.submissionIndexing"}</h3>
<p>{translate key="author.submit.submissionIndexingDescription"}</p>
<table width="100%" class="data">
{if $journalSettings.metaDiscipline}
<tr>
	<td class="label">{fieldLabel name="discipline" key="article.discipline"}</td>
	</td></td>
</tr>
<tr>
	<td></td>
	<td class="value"><input type="text" name="discipline" value="{$discipline|escape}" size="60" maxlength="255" /></td>
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
	<td class="label"><a href="{$journalSettings.metaSubjectClassUrl}" target="_blank">{$journalSettings.metaSubjectClassTitle}</a></td>
	<td></td>
<tr>
	<td class="label">{fieldLabel name="subjectClass" key="article.subjectClassification"}</td>
	<td class="value"><input type="text" name="subjectClass" value="{$subjectClass|escape}" size="60" maxlength="255" /></td>
</tr>
<tr>
	<td></td>
	<td><span class="instruct">{translate key="author.submit.subjectClassInstructions"}</span></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td></td>
</tr>
{/if}

{if $journalSettings.metaSubject}
<tr>
	<td class="label">{fieldLabel name="subject" key="article.subject"}</td>
	</td></td>
</tr>
<tr>
	<td></td>
	<td class="value"><input type="text" name="subject" value="{$subject|escape}" size="60" maxlength="255" /></td>
</tr>
{if $journalSettings.metaSubjectExamples}
<tr>
	<td></td>
	<td><span class="instruct">{$journalSettings.metaSubjectExamples}</span></td>
</tr>
{/if}
<tr>
	<td>&nbsp;</td>
	<td></td>
</tr>
{/if}

{if $journalSettings.metaCoverage}
<tr>
	<td class="label">{translate key="article.coverage"}</td>
	<td></td>
</tr>
<tr>
	<td></td>
	<td><span class="instruct">{translate key="author.submit.coverageInstructions"}</span></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="coverageGeo" key="article.coverageGeo"}</td>
	<td class="value"><input type="text" name="coverageGeo" value="{$coverageGeo|escape}" size="60" maxlength="255" /></td>
</tr>
{if $journalSettings.metaCoverageGeoExamples}
<tr>
	<td></td>
	<td><span class="instruct">{$journalSettings.metaCoverageGeoExamples}</span></td>
</tr>
{/if}
<tr>
	<td class="label">{fieldLabel name="coverageChron" key="article.coverageChron"}</td>
	<td class="value"><input type="text" name="coverageChron" value="{$coverageChron|escape}" size="60" maxlength="255" /></td>
</tr>
{if $journalSettings.metaCoverageChronExamples}
<tr>
	<td></td>
	<td><span class="instruct">{$journalSettings.metaCoverageChronExamples}</span></td>
</tr>
{/if}
<tr>
	<td class="label">{fieldLabel name="coverageSample" key="article.coverageSample"}</td>
	<td class="value"><input type="text" name="coverageSample" value="{$coverageSample|escape}" size="60" maxlength="255" /></td>
</tr>
{if $journalSettings.metaCoverageResearchSampleExamples}
<tr>
	<td></td>
	<td><span class="instruct">{$journalSettings.metaCoverageResearchSampleExamples}</span></td>
</tr>
{/if}
<tr>
	<td>&nbsp;</td>
	<td></td>
</tr>
{/if}

{if $journalSettings.metaType}
<tr>
	<td class="label">{fieldLabel name="type" key="article.type"}</td>
	</td></td>
</tr>
<tr>
	<td></td>
	<td><span class="instruct">{translate key="author.submit.typeInstructions"}</span></td>
</tr>
<tr>
	<td></td>
	<td class="value"><input type="text" name="type" value="{$type|escape}" size="60" maxlength="255" /></td>
</tr>
{if $journalSettings.metaTypeExamples}
<tr>
	<td></td>
	<td><span class="instruct">{$journalSettings.metaTypeExamples}</span></td>
</tr>
{/if}
<tr>
	<td>&nbsp;</td>
	<td></td>
</tr>
{/if}

<tr>
	<td class="label">{fieldLabel name="language" key="article.language"}</td>
	</td></td>
</tr>
<tr>
	<td></td>
	<td class="value"><input type="text" name="language" value="{$language|escape}" size="5" maxlength="10" class="textField" /></td>
</tr>
<tr>
	<td></td>
	<td><span class="instruct">{translate key="author.submit.languageInstructions"}</span></td>
</tr>
</table>

<div class="separator"></div>

<h3>{translate key="author.submit.submissionSupportingAgencies"}</h3>
<p>{translate key="author.submit.submissionSupportingAgenciesDescription"}</p>

<table width="100%" class="data">
<tr>
	<td class="label">{fieldLabel name="sponsor" key="author.submit.agencies"}</td>
	<td class="value"><input type="text" name="sponsor" value="{$sponsor|escape}" size="75" maxlength="255" /></td>
</tr>
</table>

<div class="separator"></div>

<table width="100%" class="data">
<tr>
	<td>{translate key="common.requiredField"}</td>
	<td class="value"><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="confirmAction('{$pageUrl}/author', '{translate|escape:"javascript" key="author.submit.cancelSubmission"}')" /></td>
</tr>
</table>

</form>

{include file="common/footer.tpl"}
