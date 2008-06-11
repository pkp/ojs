{**
 * metadata.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for changing metadata of an article.
 *
 * $Id$
 *}
{assign var="pageTitle" value="submission.editMetadata"}
{include file="common/header.tpl"}

{url|assign:"competingInterestGuidelinesUrl" page="information" op="competingInterestGuidelines"}

<form name="metadata" method="post" action="{url op="saveMetadata"}" enctype="multipart/form-data">
<input type="hidden" name="articleId" value="{$articleId|escape}" />
{include file="common/formErrors.tpl"}

{if $canViewAuthors}
{literal}
<script type="text/javascript">
<!--
// Move author up/down
function moveAuthor(dir, authorIndex) {
	var form = document.metadata;
	form.moveAuthor.value = 1;
	form.moveAuthorDir.value = dir;
	form.moveAuthorIndex.value = authorIndex;
	form.submit();
}
// -->
</script>
{/literal}

{if count($formLocales) > 1}
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"formUrl" path=$articleId}
			{* Maintain localized author info across requests *}
			{foreach from=$authors key=authorIndex item=author}
				{if $currentJournal->getSetting('requireAuthorCompetingInterests')}
					{foreach from=$author.competingInterests key="thisLocale" item="thisCompetingInterests"}
						{if $thisLocale != $formLocale}<input type="hidden" name="authors[{$authorIndex|escape}][competingInterests][{$thisLocale|escape}]" value="{$thisCompetingInterests|escape}" />{/if}
					{/foreach}
				{/if}
				{foreach from=$author.biography key="thisLocale" item="thisBiography"}
					{if $thisLocale != $formLocale}<input type="hidden" name="authors[{$authorIndex|escape}][biography][{$thisLocale|escape}]" value="{$thisBiography|escape}" />{/if}
				{/foreach}
			{/foreach}
			{form_language_chooser form="metadata" url=$formUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
{/if}

<h3>{translate key="article.authors"}</h3>

<input type="hidden" name="deletedAuthors" value="{$deletedAuthors|escape}" />
<input type="hidden" name="moveAuthor" value="0" />
<input type="hidden" name="moveAuthorDir" value="" />
<input type="hidden" name="moveAuthorIndex" value="" />

<table width="100%" class="data">
	{foreach name=authors from=$authors key=authorIndex item=author}
	<tr valign="top">
		<td width="20%" class="label">
			<input type="hidden" name="authors[{$authorIndex|escape}][authorId]" value="{$author.authorId|escape}" />
			<input type="hidden" name="authors[{$authorIndex|escape}][seq]" value="{$authorIndex+1}" />
			{if $smarty.foreach.authors.total <= 1}
				<input type="hidden" name="primaryContact" value="{$authorIndex|escape}" />
			{/if}
			{fieldLabel name="authors-$authorIndex-firstName" required="true" key="user.firstName"}
		</td>
		<td width="80%" class="value"><input type="text" name="authors[{$authorIndex|escape}][firstName]" id="authors-{$authorIndex|escape}-firstName" value="{$author.firstName|escape}" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors-$authorIndex-middleName" key="user.middleName"}</td>
		<td class="value"><input type="text" name="authors[{$authorIndex|escape}][middleName]" id="authors-{$authorIndex|escape}-middleName" value="{$author.middleName|escape}" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors-$authorIndex-lastName" required="true" key="user.lastName"}</td>
		<td class="value"><input type="text" name="authors[{$authorIndex|escape}][lastName]" id="authors-{$authorIndex|escape}-lastName" value="{$author.lastName|escape}" size="20" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors-$authorIndex-affiliation" key="user.affiliation"}</td>
		<td class="value"><input type="text" name="authors[{$authorIndex|escape}][affiliation]" id="authors-{$authorIndex|escape}-affiliation" value="{$author.affiliation|escape}" size="30" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors-$authorIndex-country" key="common.country"}</td>
		<td class="value">
			<select name="authors[{$authorIndex|escape}][country]" id="authors-{$authorIndex|escape}-country" class="selectMenu">
				<option value=""></option>
				{html_options options=$countries selected=$author.country|escape}
			</select>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors-$authorIndex-email" required="true" key="user.email"}</td>
		<td class="value"><input type="text" name="authors[{$authorIndex|escape}][email]" id="authors-{$authorIndex|escape}-email" value="{$author.email|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors-$authorIndex-url" key="user.url"}</td>
		<td class="value"><input type="text" name="authors[{$authorIndex|escape}][url]" id="authors-{$authorIndex|escape}-url" value="{$author.url|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	{if $currentJournal->getSetting('requireAuthorCompetingInterests')}
		<tr valign="top">
			<td width="20%" class="label">{fieldLabel name="authors-$authorIndex-competingInterests" key="author.competingInterests" competingInterestGuidelinesUrl=$competingInterestGuidelinesUrl}</td>
			<td width="80%" class="value"><textarea name="authors[{$authorIndex|escape}][competingInterests][{$formLocale|escape}]" class="textArea" id="authors-{$authorIndex|escape}-competingInterests" rows="5" cols="40">{$author.competingInterests[$formLocale]|escape}</textarea></td>
		</tr>
	{/if}{* requireAuthorCompetingInterests *}
	<tr valign="top">
		<td class="label">{fieldLabel name="authors-$authorIndex-biography" key="user.biography"}<br />{translate key="user.biography.description"}</td>
		<td class="value"><textarea name="authors[{$authorIndex|escape}][biography][{$formLocale|escape}]" id="authors-{$authorIndex|escape}-biography" rows="5" cols="40" class="textArea">{$author.biography[$formLocale]|escape}</textarea></td>
	</tr>
	{if $smarty.foreach.authors.total > 1}
	<tr valign="top">
		<td class="label">Reorder author's name</td>
		<td class="value"><a href="javascript:moveAuthor('u', '{$authorIndex|escape}')" class="action plain">&uarr;</a> <a href="javascript:moveAuthor('d', '{$authorIndex|escape}')" class="action plain">&darr;</a></td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="label"><input type="radio" name="primaryContact" id="primaryContact-{$authorIndex|escape}" value="{$authorIndex|escape}"{if $primaryContact == $authorIndex} checked="checked"{/if} /> <label for="primaryContact-{$authorIndex|escape}">{translate key="author.submit.selectPrincipalContact"}</label></td>
		<td class="labelRightPlain">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value"><input type="submit" name="delAuthor[{$authorIndex|escape}]" value="{translate key="author.submit.deleteAuthor"}" class="button" /></td>
	</tr>
	{/if}
	{if !$smarty.foreach.authors.last}
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}

	{foreachelse}
	<input type="hidden" name="authors[0][authorId]" value="0" />
	<input type="hidden" name="primaryContact" value="0" />
	<input type="hidden" name="authors[0][seq]" value="1" />
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="authors-0-firstName" required="true" key="user.firstName"}</td>
		<td width="80%" class="value"><input type="text" name="authors[0][firstName]" id="authors-0-firstName" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors-0-middleName" key="user.middleName"}</td>
		<td class="value"><input type="text" name="authors[0][middleName]" id="authors-0-middleName" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors-0-lastName" required="true" key="user.lastName"}</td>
		<td class="value"><input type="text" name="authors[0][lastName]" id="authors-0-lastName" size="20" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors-0-affiliation" key="user.affiliation"}</td>
		<td class="value"><input type="text" id="authors-0-affiliation" name="authors[0][affiliation]" size="30" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors-0-email" required="true" key="user.email"}</td>
		<td class="value"><input type="text" name="authors[0][email]" id="authors-0-email" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="authors-0-url" key="user.url"}</td>
		<td class="value"><input type="text" name="authors[0][url]" id="authors-0-url" size="30" maxlength="90" class="textField" /></td>
	</tr>
	{if $currentJournal->getSetting('requireAuthorCompetingInterests')}
		<tr valign="top">
			<td width="20%" class="label">{fieldLabel name="authors-0-competingInterests" key="author.competingInterests" competingInterestGuidelinesUrl=$competingInterestGuidelinesUrl}</td>
			<td width="80%" class="value"><textarea name="authors[0][competingInterests][{$formLocale|escape}]" class="textArea" id="authors-0-competingInterests" rows="5" cols="40"></textarea></td>
		</tr>
	{/if}
	<tr valign="top">
		<td class="label">{fieldLabel name="authors-0-biography" key="user.biography"}<br />{translate key="user.biography.description"}</td>
		<td class="value"><textarea name="authors[0][biography][{$formLocale|escape}]" id="authors-0-biography" rows="5" cols="40" class="textArea"></textarea></td>
	</tr>
	{/foreach}
</table>

<p><input type="submit" class="button" name="addAuthor" value="{translate key="author.submit.addAuthor"}" /></p>


<div class="separator"></div>
{/if}


<h3>{if $section->getAbstractsDisabled()}{translate key="article.title"}{else}{translate key="submission.titleAndAbstract"}{/if}</h3>

<table width="100%" class="data">
	<tr>
		<td width="20%" class="label">{fieldLabel name="title" required="true" key="article.title"}</td>
		<td width="80%" class="value"><input type="text" name="title[{$formLocale|escape}]" id="title" value="{$title[$formLocale]|escape}" size="60" maxlength="255" class="textField" /></td>
	</tr>

	{if !$section->getAbstractsDisabled()}
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="abstract" required="true" key="article.abstract"}</td>
		<td class="value"><textarea name="abstract[{$formLocale|escape}]" id="abstract" rows="15" cols="60" class="textArea">{$abstract[$formLocale]|escape}</textarea></td>
	</tr>
	{/if}
</table>

<div class="separator"></div>

<h3>{translate key="editor.article.cover"}</h3>

<input type="hidden" name="fileName[{$formLocale|escape}]" value="{$fileName[$formLocale]|escape}" />
<input type="hidden" name="originalFileName[{$formLocale|escape}]" value="{$originalFileName[$formLocale]|escape}" />

<table width="100%" class="data">
	<tr valign="top">
		<td class="label" colspan="2"><input type="checkbox" name="showCoverPage[{$formLocale|escape}]" id="showCoverPage" value="1" {if $showCoverPage[$formLocale]} checked="checked"{/if} /> <label for="showCoverPage">{translate key="editor.article.showCoverPage"}</label></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="coverPage" key="editor.article.coverPage"}</td>
		<td width="80%" class="value"><input type="file" name="coverPage" id="coverPage" class="uploadField" />&nbsp;&nbsp;{translate key="form.saveToUpload"}<br />{translate key="editor.article.coverPageInstructions"}<br />{translate key="editor.article.uploaded"}:&nbsp;{if $fileName[$formLocale]}<a href="javascript:openWindow('{$publicFilesDir}/{$fileName[$formLocale]|escape:"url"}');" class="file">{$originalFileName[$formLocale]}</a>&nbsp;<a href="{url op="removeArticleCoverPage" path=$articleId|to_array:$formLocale}" onclick="return confirm('{translate|escape:"jsparam" key="editor.article.removeCoverPage"}')">{translate key="editor.article.remove"}</a>{else}&mdash;{/if}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="coverPageAltText" key="common.altText"}</td>
		<td width="80%" class="value"><input type="text" name="coverPageAltText[{$formLocale|escape}]" value="{$coverPageAltText[$formLocale]|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value"><span class="instruct">{translate key="common.altTextInstructions"}</span></td>
	</tr>
</table>

<div class="separator"></div>

<h3>{translate key="submission.indexing"}</h3>

{if $journalSettings.metaDiscipline || $journalSettings.metaSubjectClass || $journalSettings.metaSubject || $journalSettings.metaCoverage || $journalSettings.metaType}<p>{translate key="author.submit.submissionIndexingDescription"}</p>{/if}

<table width="100%" class="data">
	{if $journalSettings.metaDiscipline}
	<tr valign="top">
		<td class="label">{fieldLabel name="discipline" key="article.discipline"}</td>
		<td class="value">
			<input type="text" name="discipline[{$formLocale|escape}]" id="discipline" value="{$discipline[$formLocale]|escape}" size="40" maxlength="255" class="textField" />
			{if $currentJournal->getLocalizedSetting('metaDisciplineExamples') != ''}
			<br />
			<span class="instruct">{$currentJournal->getLocalizedSetting('metaDisciplineExamples')|escape}</span>
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $journalSettings.metaSubjectClass}
	<tr valign="top">
		<td colspan="2" class="label"><a href="{$currentJournal->getLocalizedSetting('metaSubjectClassUrl')|escape}" target="_blank">{$currentJournal->getLocalizedSetting('metaSubjectClassTitle')|escape}</a></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="subjectClass" key="article.subjectClassification"}</td>
		<td class="value">
			<input type="text" name="subjectClass[{$formLocale|escape}]" id="subjectClass" value="{$subjectClass[$formLocale]|escape}" size="40" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="author.submit.subjectClassInstructions"}</span>
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $journalSettings.metaSubject}
	<tr valign="top">
		<td class="label">{fieldLabel name="subject" key="article.subject"}</td>
		<td class="value">
			<input type="text" name="subject[{$formLocale|escape}]" id="subject" value="{$subject[$formLocale]|escape}" size="40" maxlength="255" class="textField" />
			{if $currentJournal->getLocalizedSetting('metaSubjectExamples') != ''}
			<br />
			<span class="instruct">{$currentJournal->getLocalizedSetting('metaSubjectExamples')|escape}</span>
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $journalSettings.metaCoverage}
	<tr valign="top">
		<td class="label">{fieldLabel name="coverageGeo" key="article.coverageGeo"}</td>
		<td class="value">
			<input type="text" name="coverageGeo[{$formLocale|escape}]" id="coverageGeo" value="{$coverageGeo[$formLocale]|escape}" size="40" maxlength="255" class="textField" />
			{if $currentJournal->getLocalizedSetting('metaCoverageGeoExamples') != ''}
			<br />
			<span class="instruct">{$currentJournal->getLocalizedSetting('metaCoverageGeoExamples')|escape}</span>
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="coverageChron" key="article.coverageChron"}</td>
		<td class="value">
			<input type="text" name="coverageChron[{$formLocale|escape}]" id="coverageChron" value="{$coverageChron[$formLocale]|escape}" size="40" maxlength="255" class="textField" />
			{if $currentJournal->getLocalizedSetting('metaCoverageChronExamples') != ''}
			<br />
			<span class="instruct">{$currentJournal->getLocalizedSetting('metaCoverageChronExamples')|escape}</span>
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="coverageSample" key="article.coverageSample"}</td>
		<td class="value">
			<input type="text" name="coverageSample[{$formLocale|escape}]" id="coverageSample" value="{$coverageSample[$formLocale]|escape}" size="40" maxlength="255" class="textField" />
			{if $currentJournal->getLocalizedSetting('metaCoverageResearchSampleExamples') != ''}
			<br />
			<span class="instruct">{$currentJournal->getLocalizedSetting('metaCoverageResearchSampleExamples')|escape}</span>
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $journalSettings.metaType}
	<tr valign="top">
		<td class="label">{fieldLabel name="type" key="article.type"}</td>
		<td class="value">
			<input type="text" name="type[{$formLocale|escape}]" id="type" value="{$type[$formLocale]|escape}" size="40" maxlength="255" class="textField" />
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
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
			<input type="text" name="sponsor[{$formLocale|escape}]" id="sponsor" value="{$sponsor[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
		</td>
	</tr>
</table>


<div class="separator"></div>


<h3>{translate key="editor.article.display"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="hideAuthor" key="issue.toc"}</td>
		<td width="80%" class="value">{translate key="editor.article.hideTocAuthorDescription"}: 
			<select name="hideAuthor" id="hideAuthor" class="selectMenu">
				{html_options options=$hideAuthorOptions selected=$hideAuthor|escape}
			</select>
		</td>
	</tr>
</table>


<div class="separator"></div>


<p><input type="submit" value="{translate key="submission.saveMetadata"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1)" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
