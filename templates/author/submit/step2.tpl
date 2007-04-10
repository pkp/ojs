{**
 * step2.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 2 of author article submission.
 *
 * $Id$
 *}

{assign var="pageTitle" value="author.submit.step2"}
{include file="author/submit/submitHeader.tpl"}
<p>{translate key="author.submit.metadataDescription"}</p>
<h3>{translate key="author.submit.privacyStatement"}</h3>
<br />
{$journalSettings.privacyStatement|nl2br}

<div class="separator"></div>

<form name="submit" method="post" action="{url op="saveSubmit" path=$submitStep}">
<input type="hidden" name="articleId" value="{$articleId}" />
{include file="common/formErrors.tpl"}

{literal}
<script type="text/javascript">
<!--
// Move author up/down
function moveAuthor(dir, authorIndex) {
	var form = document.submit;
	form.moveAuthor.value = 1;
	form.moveAuthorDir.value = dir;
	form.moveAuthorIndex.value = authorIndex;
	form.submit();
}
// -->
</script>
{/literal}

<h3>{translate key="article.authors"}</h3>
<p>{translate key="author.submit.authorsDescription"}</p>
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
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-$authorIndex-firstName" required="true" key="user.firstName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="authors[{$authorIndex}][firstName]" id="authors-{$authorIndex}-firstName" value="{$author.firstName|escape}" size="20" maxlength="40" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-$authorIndex-middleName" key="user.middleName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="authors[{$authorIndex}][middleName]" id="authors-{$authorIndex}-middleName" value="{$author.middleName|escape}" size="20" maxlength="40" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-$authorIndex-lastName" required="true" key="user.lastName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="authors[{$authorIndex}][lastName]" id="authors-{$authorIndex}-lastName" value="{$author.lastName|escape}" size="20" maxlength="90" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-$authorIndex-affiliation" key="user.affiliation"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="authors[{$authorIndex}][affiliation]" id="authors-{$authorIndex}-affiliation" value="{$author.affiliation|escape}" size="30" maxlength="255"/></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-$authorIndex-country" key="common.country"}</td>
	<td width="80%" class="value">
		<select name="authors[{$authorIndex}][country]" id="authors-{$authorIndex}-country" class="selectMenu">
			<option value=""></option>
			{html_options options=$countries selected=$author.country}
		</select>
	</td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-$authorIndex-email" required="true" key="user.email"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="authors[{$authorIndex}][email]" id="authors-{$authorIndex}-email" value="{$author.email|escape}" size="30" maxlength="90" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-$authorIndex-url" key="user.url"}</td>
	<td width="80%" class="value">
		<input type="text" class="textField" name="authors[{$authorIndex}][url]" id="authors-{$authorIndex}-url" value="{$author.url|escape}" size="30" maxlength="90" /><br/>
		<span class="instruct">{translate key="user.url.description"}</span>
	</td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-$authorIndex-biography" key="user.biography"}<br />{translate key="user.biography.description"}</td>
	<td width="80%" class="value"><textarea name="authors[{$authorIndex}][biography]" class="textArea" id="authors-{$authorIndex}-biography" rows="5" cols="40">{$author.biography|escape}</textarea></td>
</tr>
{if $smarty.foreach.authors.total > 1}
<tr valign="top">
	<td colspan="2">
		{translate key="author.submit.reorderAuthorName"} <a href="javascript:moveAuthor('u', '{$authorIndex}')" class="action">&uarr;</a> <a href="javascript:moveAuthor('d', '{$authorIndex}')" class="action">&darr;</a><br/>
		{translate key="author.submit.reorderInstructions"}
	</td>
</tr>
<tr valign="top">
	<td width="80%" class="value" colspan="2"><input type="radio" name="primaryContact" value="{$authorIndex}"{if $primaryContact == $authorIndex} checked="checked"{/if} /> <label for="primaryContact">{translate key="author.submit.selectPrincipalContact"}</label> <input type="submit" name="delAuthor[{$authorIndex}]" value="{translate key="author.submit.deleteAuthor"}" class="button" /></td>
</tr>
<tr>
	<td colspan="2"><br/></td>
</tr>
{/if}
</table>
{foreachelse}
<input type="hidden" name="authors[0][authorId]" value="0" />
<input type="hidden" name="primaryContact" value="0" />
<input type="hidden" name="authors[0][seq]" value="1" />
<table width="100%' class="data">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-0-firstName" required="true" key="user.firstName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="authors[0][firstName]" id="authors-0-firstName" size="20" maxlength="40" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-0-middleName" key="user.middleName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="authors[0][middleName]" id="authors-0-middleName" size="20" maxlength="40" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-0-lastName" required="true" key="user.lastName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="authors[0][lastName]" id="authors-0-lastName" size="20" maxlength="90" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-0-affiliation" key="user.affiliation"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="authors[0][affiliation]" id="authors-0-affiliation" size="30" maxlength="255" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-0-country" key="common.country"}</td>
	<td width="80%" class="value">
		<select name="authors[0][country]" id="authors-0-country" class="selectMenu">
			<option value=""></option>
			{html_options options=$countries}
		</select>
	</td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-0-email" required="true" key="user.email"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="authors[0][email]" id="authors-0-email" size="30" maxlength="90" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-0-biography" key="user.biography"}<br />{translate key="user.biography.description"}</td>
	<td width="80%" class="value"><textarea name="authors[0][biography]" class="textArea" id="authors-0-biography" rows="5" cols="40"></textarea></td>
</tr>
</table>
{/foreach}

<p><input type="submit" class="button" name="addAuthor" value="{translate key="author.submit.addAuthor"}" /></p>

<div class="separator"></div>

<h3>{if $section->getAbstractsDisabled()==1}{translate key="article.title"}{else}{translate key="submission.titleAndAbstract"}{/if}</h3>

<table width="100%" class="data">

<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="title" required="true" key="article.title"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="title" id="title" value="{$title|escape}" size="60" maxlength="255" /></td>
</tr>
{if $alternateLocale1}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="titleAlt1" key="article.title"} ({$languageToggleLocales.$alternateLocale1|escape})</td>
	<td width="80%" class="value"><input type="text" class="textField" name="titleAlt1" id="titleAlt1" value="{$titleAlt1|escape}" size="60" maxlength="255" /></td>
</tr>
{/if}
{if $alternateLocale2}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="titleAlt2" key="article.title"} ({$languageToggleLocales.$alternateLocale2|escape})</td>
	<td width="80%" class="value"><input type="text" class="textField" name="titleAlt2" id="titleAlt2" value="{$titleAlt2|escape}" size="60" maxlength="255" /></td>
</tr>
{/if}

{if $section->getAbstractsDisabled()==0}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="abstract" required="true" key="article.abstract"}</td>
	<td width="80%" class="value"><textarea name="abstract" id="abstract" class="textArea" rows="15" cols="60">{$abstract|escape}</textarea></td>
</tr>
{if $alternateLocale1}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="abstractAlt1" key="article.abstract"} ({$languageToggleLocales.$alternateLocale1|escape})</td>
	<td width="80%" class="value"><textarea name="abstractAlt1" class="textArea" id="abstractAlt1" rows="15" cols="60">{$abstractAlt1|escape}</textarea></td>
</tr>
{/if}
{if $alternateLocale2}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="abstractAlt2" key="article.abstract"} ({$languageToggleLocales.$alternateLocale2|escape})</td>
	<td width="80%" class="value"><textarea name="abstractAlt2" class="textArea" id="abstractAlt2" rows="15" cols="60">{$abstractAlt2|escape}</textarea></td>
</tr>
{/if}
{/if}{* Abstracts enabled *}
</table>

<div class="separator"></div>

{if $section->getMetaIndexed()==1}
	<h3>{translate key="submission.indexing"}</h3>
	<p>{translate key="author.submit.submissionIndexingDescription"}</p>
	<table width="100%" class="data">
	{if $journalSettings.metaDiscipline}
	<tr valign="top">
		<td{if $journalSettings.metaDisciplineExamples} rowspan="2"{/if} width="20%" class="label">{fieldLabel name="discipline" key="article.discipline"}</td>
		<td width="80%" class="value"><input type="text" class="textField" name="discipline" id="discipline" value="{$discipline|escape}" size="40" maxlength="255" /></td>
	</tr>
	{if $journalSettings.metaDisciplineExamples}
	<tr valign="top">
		<td><span class="instruct">{$journalSettings.metaDisciplineExamples|escape}</span></td>
	</tr>
	{/if}
	<tr valign="top">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	{/if}
	
	{if $journalSettings.metaSubjectClass}
	<tr valign="top">
		<td rowspan="2" width="20%" class="label">{fieldLabel name="subjectClass" key="article.subjectClassification"}</td>
		<td width="80%" class="value"><input type="text" class="textField" name="subjectClass" id="subjectClass" value="{$subjectClass|escape}" size="40" maxlength="255" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><a href="{$journalSettings.metaSubjectClassUrl|escape}" target="_blank">{$journalSettings.metaSubjectClassTitle|escape}</a></td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	{/if}
	
	{if $journalSettings.metaSubject}
	<tr valign="top">
		<td{if $journalSettings.metaSubjectExamples} rowspan="2"{/if} width="20%" class="label">{fieldLabel name="subject" key="article.subject"}</td>
		<td width="80%" class="value"><input type="text" class="textField" name="subject" id="subject" value="{$subject|escape}" size="40" maxlength="255" /></td>
	</tr>
	{if $journalSettings.metaSubjectExamples}
	<tr valign="top">
		<td><span class="instruct">{$journalSettings.metaSubjectExamples|escape}</span></td>
	</tr>
	{/if}
	<tr valign="top">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	{/if}
	
	{if $journalSettings.metaCoverage}
	<tr valign="top">
		<td{if $journalSettings.metaCoverageGeoExamples} rowspan="2"{/if} width="20%" class="label">{fieldLabel name="coverageGeo" key="article.coverageGeo"}</td>
		<td width="80%" class="value"><input type="text" class="textField" name="coverageGeo" id="coverageGeo" value="{$coverageGeo|escape}" size="40" maxlength="255" /></td>
	</tr>
	{if $journalSettings.metaCoverageGeoExamples}
	<tr valign="top">
		<td><span class="instruct">{$journalSettings.metaCoverageGeoExamples|escape}</span></td>
	</tr>
	{/if}
	<tr valign="top">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr valign="top">
		<td{if $journalSettings.metaCoverageChronExamples} rowspan="2"{/if} width="20%" class="label">{fieldLabel name="coverageChron" key="article.coverageChron"}</td>
		<td width="80%" class="value"><input type="text" class="textField" name="coverageChron" id="coverageChron" value="{$coverageChron|escape}" size="40" maxlength="255" /></td>
	</tr>
	{if $journalSettings.metaCoverageChronExamples}
	<tr valign="top">
		<td><span class="instruct">{$journalSettings.metaCoverageChronExamples|escape}</span></td>
	</tr>
	{/if}
	<tr valign="top">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr valign="top">
		<td{if $journalSettings.metaCoverageResearchSampleExamples} rowspan="2"{/if} width="20%" class="label">{fieldLabel name="coverageSample" key="article.coverageSample"}</td>
		<td width="80%" class="value"><input type="text" class="textField" name="coverageSample" id="coverageSample" value="{$coverageSample|escape}" size="40" maxlength="255" /></td>
	</tr>
	{if $journalSettings.metaCoverageResearchSampleExamples}
	<tr valign="top">
		<td><span class="instruct">{$journalSettings.metaCoverageResearchSampleExamples|escape}</span></td>
	</tr>
	{/if}
	<tr valign="top">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	{/if}
	
	{if $journalSettings.metaType}
	<tr valign="top">
		<td width="20%" {if $journalSettings.metaTypeExamples}rowspan="2" {/if}class="label">{fieldLabel name="type" key="article.type"}</td>
		<td width="80%" class="value"><input type="text" class="textField" name="type" id="type" value="{$type|escape}" size="40" maxlength="255" /></td>
	</tr>

	{if $journalSettings.metaTypeExamples}
	<tr valign="top">
		<td><span class="instruct">{$journalSettings.metaTypeExamples|escape}</span></td>
	</tr>
	{/if}
	<tr valign="top">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	{/if}
	
	<tr valign="top">
		<td rowspan="2" width="20%" class="label">{fieldLabel name="language" key="article.language"}</td>
		<td width="80%" class="value"><input type="text" class="textField" name="language" id="language" value="{$language|escape|default:en}" size="5" maxlength="10" /></td>
	</tr>
	<tr valign="top">
		<td><span class="instruct">{translate key="author.submit.languageInstructions"}</span></td>
	</tr>
	</table>

<div class="separator"></div>

{/if}


<h3>{translate key="author.submit.submissionSupportingAgencies"}</h3>
<p>{translate key="author.submit.submissionSupportingAgenciesDescription"}</p>

<table width="100%" class="data">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="sponsor" key="author.submit.agencies"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="sponsor" id="sponsor" value="{$sponsor|escape}" size="60" maxlength="255" /></td>
</tr>
</table>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="confirmAction('{url page="author"}', '{translate|escape:"javascript" key="author.submit.cancelSubmission"}')" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
