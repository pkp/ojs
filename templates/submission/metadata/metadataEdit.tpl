{**
 * metadata.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for changing metadata of an article.
 *
 * $Id$
 *}
 
{assign var="pageTitle" value="submission.editMetadata"}
{assign var="pageId" value="submission.metadata.metadataEdit"}
{include file="common/header.tpl"}

<form method="post" action="{$requestPageUrl}/saveMetadata">
<input type="hidden" name="articleId" value="{$articleId}" />
{include file="common/formErrors.tpl"}

{if $canViewAuthors}
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

<h3>{translate key="article.authors"}</h3>

<input type="hidden" name="deletedAuthors" value="{$deletedAuthors|escape}" />
<input type="hidden" name="moveAuthor" value="0" />
<input type="hidden" name="moveAuthorDir" value="" />
<input type="hidden" name="moveAuthorIndex" value="" />

<table width="100%" class="data">
	{foreach name=authors from=$authors key=authorIndex item=author}
	<input type="hidden" name="authors[{$authorIndex}][authorId]" value="{$author.authorId|escape}" />
	<input type="hidden" name="authors[{$authorIndex}][seq]" value="{$authorIndex+1}" />
	{if $smarty.foreach.authors.total <= 1}
	<input type="hidden" name="primaryContact" value="{$authorIndex}" />
	{/if}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="authors[$authorIndex][firstName]" required="true" key="user.firstName"}</td>
		<td width="80%" class="value"><input type="text" name="authors[{$authorIndex}][firstName]" id="authors[{$authorIndex}][firstName]" value="{$author.firstName|escape}" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors[{$authorIndex][middleName]" key="user.middleName"}</td>
		<td class="value"><input type="text" name="authors[{$authorIndex}][middleName]" id="authors[{$authorIndex}][middleName]" value="{$author.middleName|escape}" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors[$authorIndex][lastName]" required="true" key="user.lastName"}</td>
		<td class="value"><input type="text" name="authors[{$authorIndex}][lastName]" id="authors[{$authorIndex}][lastName]" value="{$author.lastName|escape}" size="20" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors[$authorIndex][affiliation]" key="user.affiliation"}</td>
		<td class="value"><input type="text" name="authors[{$authorIndex}][affiliation]" id="authors[{$authorIndex}][affiliation]" value="{$author.affiliation|escape}" size="30" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors[$authorIndex][email]" required="true" key="user.email"}</td>
		<td class="value"><input type="text" name="authors[{$authorIndex}][email]" id="authors[{$authorIndex}][email]" value="{$author.email|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors[$authorIndex][biography]" key="user.biography"}</td>
		<td class="value"><textarea name="authors[{$authorIndex}][biography]" id="authors[{$authorIndex}][biography]" rows="5" cols="40" class="textArea">{$author.biography|escape}</textarea></td>
	</tr>
	{if $smarty.foreach.authors.total > 1}
	<tr valign="top">
		<td class="label">Reorder author's name</td>
		<td class="value"><a href="javascript:moveAuthor('u', '{$authorIndex}')" class="action plain">&uarr;</a> <a href="javascript:moveAuthor('d', '{$authorIndex}')" class="action plain">&darr;</a></td>
	</tr>
	<tr valign="top">
		<td></td>
		<td class="label"><input type="radio" name="primaryContact" id="primaryContact[{$authorIndex}]" value="{$authorIndex}"{if $primaryContact == $authorIndex} checked="checked"{/if} /> <label for="primaryContact[{$authorIndex}]">{translate key="author.submit.selectPrincipalContact"}</label></td>
		<td class="labelRightPlain"></td>
	</tr>
	<tr valign="top">
		<td></td>
		<td class="value"><input type="submit" name="delAuthor[{$authorIndex}]" value="{translate key="author.submit.deleteAuthor"}" class="button" /></td>
	</tr>
	{/if}
	{if !$smarty.foreach.authors.last}
	<tr>
		<td colspan="2" class="separator"></td>
	</tr>
	{/if}

	{foreachelse}
	<input type="hidden" name="authors[0][authorId]" value="0" />
	<input type="hidden" name="primaryContact" value="0" />
	<input type="hidden" name="authors[0][seq]" value="1" />
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="authors[0][firstName]" required="true" key="user.firstName"}</td>
		<td width="80%" class="value"><input type="text" name="authors[0][firstName]" id="authors[0][firstName]" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors[0][middleName]" key="user.middleName"}</td>
		<td class="value"><input type="text" name="authors[0][middleName]" id="authors[0][middleName]" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors[0][lastName]" required="true" key="user.lastName"}</td>
		<td class="value"><input type="text" name="authors[0][lastName]" id="authors[0][lastName]" size="20" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors[0][affiliation]" key="user.affiliation"}</td>
		<td class="value"><input type="text" name="authors[0][affiliation]" id="authors[0][affiliation]" size="30" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors[0][email]" required="true" key="user.email"}</td>
		<td class="value"><input type="text" name="authors[0][email]" id="authors[0][email]" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors[0][biography]" key="user.biography"}</td>
		<td class="value"><textarea name="authors[0][biography]" ids="authors[0][biography]" rows="5" cols="40" class="textArea"></textarea></td>
	</tr>
	{/foreach}
</table>

<p><input type="submit" class="button" name="addAuthor" value="{translate key="author.submit.addAuthor"}" /></p>


<div class="separator"></div>
{/if}


<h3>{translate key="submission.titleAndAbstract"}</h3>

<table width="100%" class="data">
	<tr>
		<td width="20%" class="label">{fieldLabel name="title" required="true" key="article.title"}</td>
		<td width="80%" class="value"><input type="text" name="title" id="title" value="{$title|escape}" size="60" maxlength="255" class="textField" /></td>
	</tr>
	{if $alternateLocale1}
	<tr valign="top">
		<td class="label">{fieldLabel name="titleAlt1" key="article.title"}<br />({$languageToggleLocales.$alternateLocale1})</td>
		<td class="value"><input type="text" name="titleAlt1" id="titleAlt1" value="{$titleAlt1|escape}" size="60" maxlength="255" class="textField" /></td>
	</tr>
	{/if}
	{if $alternateLocale2}
	<tr valign="top">
		<td class="label">{fieldLabel name="titleAlt2" key="article.title"}<br />({$languageToggleLocales.$alternateLocale2})</td>
		<td class="value"><input type="text" name="titleAlt2" id="titleAlt2" value="{$titleAlt2|escape}" size="60" maxlength="255" class="textField" /></td>
	</tr>
	{/if}
	<tr>
		<td colspan="2" class="separator"></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="abstract" key="article.abstract"}</td>
		<td class="value"><textarea name="abstract" id="abstract" rows="15" cols="60" class="textArea">{$abstract|escape}</textarea></td>
	</tr>
	{if $alternateLocale1}
	<tr valign="top">
		<td class="label">{fieldLabel name="abstractAlt1" key="article.abstract"}<br />({$languageToggleLocales.$alternateLocale1})</td>
		<td class="value"><textarea name="abstractAlt1" id="abstractAlt1" rows="15" cols="60" class="textArea">{$abstractAlt1|escape}</textarea></td>
	</tr>
	{/if}
	{if $alternateLocale2}
	<tr valign="top">
		<td class="label">{fieldLabel name="abstractAlt2" key="article.abstract"}<br />({$languageToggleLocales.$alternateLocale2})</td>
		<td class="value"><textarea name="abstractAlt2" id="abstractAlt2" rows="15" cols="60" class="textArea">{$abstractAlt2|escape}</textarea></td>
	</tr>
	{/if}
</table>


<div class="separator"></div>


<h3>{translate key="submission.indexing"}</h3>

<p>{translate key="author.submit.submissionIndexingDescription"}</p>

<table width="100%" class="data">
	{if $journalSettings.metaDiscipline}
	<tr valign="top">
		<td class="label">{fieldLabel name="discipline" key="article.discipline"}</td>
		<td class="value">
			<input type="text" name="discipline" id="discipline" value="{$discipline|escape}" size="40" maxlength="255" class="textField" />
			{if $journalSettings.metaDisciplineExamples}
			<br />
			<span class="instruct">{$journalSettings.metaDisciplineExamples}</span>
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator"></td>
	</tr>
	{/if}
	{if $journalSettings.metaSubjectClass}
	<tr valign="top">
		<td colspan="2" class="label"><a href="submit/{$journalSettings.metaSubjectClassUrl}" target="_blank">{$journalSettings.metaSubjectClassTitle}</a></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="subjectClass" key="article.subjectClassification"}</td>
		<td class="value">
			<input type="text" name="subjectClass" id="subjectClass" value="{$subjectClass|escape}" size="40" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="author.submit.subjectClassInstructions"}</span>
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator"></td>
	</tr>
	{/if}
	{if $journalSettings.metaSubject}
	<tr valign="top">
		<td class="label">{fieldLabel name="subject" key="article.subject"}</td>
		<td class="value">
			<input type="text" name="subject" id="subject" value="{$subject|escape}" size="40" maxlength="255" class="textField" />
			{if $journalSettings.metaSubjectExamples}
			<br />
			<span class="instruct">{$journalSettings.metaSubjectExamples}</span>
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator"></td>
	</tr>
	{/if}
	{if $journalSettings.metaCoverage}
	<tr valign="top">
		<td class="label">{fieldLabel name="coverageGeo" key="article.coverageGeo"}</td>
		<td class="value">
			<input type="text" name="coverageGeo" id="coverageGeo" value="{$coverageGeo|escape}" size="40" maxlength="255" class="textField" />
			{if $journalSettings.metaCoverageGeoExamples}
			<br />
			<span class="instruct">{$journalSettings.metaCoverageGeoExamples}</span>
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator"></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="coverageChron" key="article.coverageChron"}</td>
		<td class="value">
			<input type="text" name="coverageChron" id="coverageChron" value="{$coverageChron|escape}" size="40" maxlength="255" class="textField" />
			{if $journalSettings.metaCoverageChronExamples}
			<br />
			<span class="instruct">{$journalSettings.metaCoverageChronExamples}</span>
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator"></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="coverageSample" key="article.coverageSample"}</td>
		<td class="value">
			<input type="text" name="coverageSample" id="coverageSample" value="{$coverageSample|escape}" size="40" maxlength="255" class="textField" />
			{if $journalSettings.metaCoverageResearchSampleExamples}
			<br />
			<span class="instruct">{$journalSettings.metaCoverageResearchSampleExamples}</span>
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator"></td>
	</tr>
	{/if}
	{if $journalSettings.metaType}
	<tr valign="top">
		<td class="label">{fieldLabel name="type" key="article.type"}</td>
		<td class="value">
			<input type="text" name="type" id="type" value="{$type|escape}" size="40" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="author.submit.typeInstructions"}</span>
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator"></td>
	</tr>
	{/if}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="language" key="article.language"}</td>
		<td width="80%" class="value">
			<input type="text" name="language" id="language" value="{$language|escape}" size="5" maxlength="10" class="textField" />
			<br />
			<span class="instruct">{translate key="author.submit.languageInstructions"}</span>
		</td>
	</tr>
</table>


<div class="separator"></div>


<h3>{translate key="submission.supportingAgencies"}</h3>

<p>{translate key="author.submit.submissionSupportingAgenciesDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsor" key="author.submit.agencies"}</td>
		<td width="80%" class="value">
			<input type="text" name="sponsor" id="sponsor" value="{$sponsor|escape}" size="60" maxlength="255" class="textField" />
		</td>
	</tr>
</table>


<div class="separator"></div>


<p><input type="submit" value="{translate key="submission.saveMetadata"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
