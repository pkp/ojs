{**
 * metadata.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the submission metadata table.
 *
 * $Id$
 *}

<a name="metadata"></a>
<h3>{translate key="submission.metadata"}</h3>

<p><a href="{$requestPageUrl}/viewMetadata/{$submission->getArticleId()}" class="action">{translate key="submission.editMetadata"}</a></p>


<h4>{translate key="article.authors"}</h4>
	
<table width="100%" class="data">
	{foreach name=authors from=$authors item=author}
	<tr valign="top">
		<td width="20%" class="label">{translate key="user.name"}</td>
		<td width="80%" class="value">
			{assign var=emailString value="`$author->getFullName()` <`$author->getEmail()`>"}
			{assign var=emailStringEscaped value=$emailString|escape:"url"}
			{assign var=urlEscaped value=$currentUrl|escape:"url"}
			{assign var=subjectEscaped value=$submission->getArticleTitle()|escape:"url"}
			{$author->getFullName()|escape} {icon name="mail" url="`$pageUrl`/user/email?to[]=$emailStringEscaped&redirectUrl=$urlEscaped&subject=$subjectEscaped"}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.affiliation"}</td>
		<td class="value">{$author->getAffiliation()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.biography"}</td>
		<td class="value">{$author->getBiography()|escape|nl2br|default:"&mdash;"}</td>
	</tr>
	{if $author->getPrimaryContact()}
	<tr valign="top">
		<td colspan="2" class="label">{translate key="author.submit.selectPrincipalContact"}</td>
	</tr>
	{/if}
	{if !$smarty.foreach.authors.last}
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{/foreach}
</table>


<br />


<h4>{if $section->getAbstractsDisabled()}{translate key="article.title"}{else}{translate key="submission.titleAndAbstract"}{/if}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.title"}</td>
		<td width="80%" class="value">{$submission->getTitle()|escape|default:"&mdash;"}</td>
	</tr>
	{if $alternateLocale1}
	<tr valign="top">
		<td class="label">{translate key="article.title"}<br />({$languageToggleLocales.$alternateLocale1})</td>
		<td class="value">{$submission->getTitleAlt1()|escape|default:"&mdash;"}</td>
	</tr>
	{/if}
	{if $alternateLocale2}
	<tr valign="top">
		<td class="label">{translate key="article.title"}<br />({$languageToggleLocales.$alternateLocale2})</td>
		<td class="value">{$submission->getTitleAlt2()|escape|default:"&mdash;"}</td>
	</tr>
	{/if}

	{if !$section->getAbstractsDisabled()}
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="article.abstract"}</td>
		<td class="value">{$submission->getAbstract()|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
	</tr>
	{if $alternateLocale1}
	<tr valign="top">
		<td class="label">{translate key="article.abstract"}<br />({$languageToggleLocales.$alternateLocale1})</td>
		<td class="value">{$submission->getAbstractAlt1()|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
	</tr>
	{/if}
	{if $alternateLocale2}
	<tr valign="top">
		<td class="label">{translate key="article.abstract"}<br />({$languageToggleLocales.$alternateLocale2})</td>
		<td class="value">{$submission->getAbstractAlt2()|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
	</tr>
	{/if}
	{/if}
</table>


<br />


<h4>{translate key="submission.indexing"}</h4>
	
<table width="100%" class="data">
	{if $journalSettings.metaDiscipline}
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.discipline"}</td>
		<td width="80%" class="value">{$submission->getDiscipline()|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $journalSettings.metaSubjectClass}
	<tr valign="top">
		<td width="20%"  class="label">{translate key="article.subjectClassification"}</td>
		<td width="80%" class="value">{$submission->getSubjectClass()|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $journalSettings.metaSubject}
	<tr valign="top">
		<td width="20%"  class="label">{translate key="article.subject"}</td>
		<td width="80%" class="value">{$submission->getSubject()|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $journalSettings.metaCoverage}
	<tr valign="top">
		<td width="20%"  class="label">{translate key="article.coverageGeo"}</td>
		<td width="80%" class="value">{$submission->getCoverageGeo()|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="article.coverageChron"}</td>
		<td class="value">{$submission->getCoverageChron()|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="article.coverageSample"}</td>
		<td class="value">{$submission->getCoverageSample()|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $journalSettings.metaType}
	<tr valign="top">
		<td width="20%"  class="label">{translate key="article.type"}</td>
		<td width="80%" class="value">{$submission->getType()|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.language"}</td>
		<td width="80%" class="value">{$submission->getLanguage()|escape|default:"&mdash;"}</td>
	</tr>
</table>


<br />


<h4>{translate key="submission.supportingAgencies"}</h4>
	
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="author.submit.agencies"}</td>
		<td width="80%" class="value">{$submission->getSponsor()|escape|default:"&mdash;"}</td>
	</tr>
</table>
