{**
 * metadata.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
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
	{foreach name=authors from=$submission->getAuthors() item=author}
	<tr valign="top">
		<td width="20%" class="label">{translate key="user.name"}</td>
		<td width="80%" class="value">{$author->getFullName()} {icon name="mail" url="mailto:`$author->getEmail()`"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.affiliation"}</td>
		<td class="value">{$author->getAffiliation()|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.biography"}</td>
		<td class="value">{$author->getBiography()|nl2br|default:"&mdash;"}</td>
	</tr>
	{if $author->getPrimaryContact()}
	<tr valign="top">
		<td colspan="2" class="label">{translate key="author.submit.selectPrincipalContact"}</td>
	</tr>
	{/if}
	{if !$smarty.foreach.authors.last}
	<tr>
		<td colspan="2" class="separator"></td>
	</tr>
	{/if}
	{/foreach}
</table>


<br />


<h4>{translate key="submission.titleAndAbstract"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.title"}</td>
		<td width="80%" class="value">{$submission->getTitle()|default:"&mdash;"}</td>
	</tr>
	{if $alternateLocale1}
	<tr valign="top">
		<td class="label">{translate key="article.title"}<br />({$languageToggleLocales.$alternateLocale1})</td>
		<td class="value">{$submission->getTitleAlt1()|default:"&mdash;"}</td>
	</tr>
	{/if}
	{if $alternateLocale2}
	<tr valign="top">
		<td class="label">{translate key="article.title"}<br />({$languageToggleLocales.$alternateLocale2})</td>
		<td class="value">{$submission->getTitleAlt2()|default:"&mdash;"}</td>
	</tr>
	{/if}
	<tr>
		<td colspan="2" class="separator"></td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="article.abstract"}</td>
		<td class="value">{$submission->getAbstract()|nl2br|default:"&mdash;"}</td>
	</tr>
	{if $alternateLocale1}
	<tr valign="top">
		<td class="label">{translate key="article.abstract"}<br />({$languageToggleLocales.$alternateLocale1})</td>
		<td class="value">{$submission->getAbstractAlt1()|nl2br|default:"&mdash;"}</td>
	</tr>
	{/if}
	{if $alternateLocale2}
	<tr valign="top">
		<td class="label">{translate key="article.abstract"}<br />({$languageToggleLocales.$alternateLocale2})</td>
		<td class="value">{$submission->getAbstractAlt2()|nl2br|default:"&mdash;"}</td>
	</tr>
	{/if}
</table>


<br />


<h4>{translate key="submission.indexing"}</h4>
	
<table width="100%" class="data">
	{if $journalSettings.metaDiscipline}
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.discipline"}</td>
		<td width="80%" class="value">{$submission->getDiscipline()|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator"></td>
	</tr>
	{/if}
	{if $journalSettings.metaSubjectClass}
	<tr valign="top">
		<td width="20%"  class="label">{translate key="article.subjectClassification"}</td>
		<td width="80%" class="value">{$submission->getSubjectClass()|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator"></td>
	</tr>
	{/if}
	{if $journalSettings.metaSubject}
	<tr valign="top">
		<td width="20%"  class="label">{translate key="article.subject"}</td>
		<td width="80%" class="value">{$submission->getSubject()|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator"></td>
	</tr>
	{/if}
	{if $journalSettings.metaCoverage}
	<tr valign="top">
		<td width="20%"  class="label">{translate key="article.coverageGeo"}</td>
		<td width="80%" class="value">{$submission->getCoverageGeo()|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator"></td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="article.coverageChron"}</td>
		<td class="value">{$submission->getCoverageChron()|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator"></td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="article.coverageSample"}</td>
		<td class="value">{$submission->getCoverageSample()|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator"></td>
	</tr>
	{/if}
	{if $journalSettings.metaType}
	<tr valign="top">
		<td width="20%"  class="label">{translate key="article.type"}</td>
		<td width="80%" class="value">{$submission->getType()|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator"></td>
	</tr>
	{/if}
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.language"}</td>
		<td width="80%" class="value">{$submission->getLanguage()|default:"&mdash;"}</td>
	</tr>
</table>


<br />


<h4>{translate key="submission.supportingAgencies"}</h4>
	
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="author.submit.agencies"}</td>
		<td width="80%" class="value">{$submission->getSponsor()|default:"&mdash;"}</td>
	</tr>
</table>
