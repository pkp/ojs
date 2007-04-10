{**
 * metadata_view.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * View (but not edit) metadata of an article.
 *
 * $Id$
 *}
 
{assign var="pageTitle" value="submission.viewMetadata"}
{include file="common/header.tpl"}

{if $canViewAuthors}
<h3>{translate key="article.authors"}</h3>
	
<table width="100%" class="data">
	{foreach name=authors from=$authors key=authorIndex item=author}
	<tr valign="top">
		<td width="20%" class="label">{translate key="user.name"}</td>
		<td width="80%" class="value">
			{assign var=emailString value="`$author.firstName` `$author.middleName` `$author.lastName` <`$author.email`>"}
			{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl articleId=$articleId}
			{$author.firstName|escape} {$author.middleName|escape} {$author.lastName|escape} {icon name="mail" url=$url}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.url"}</td>
		<td class="value">{$author.url|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.affiliation"}</td>
		<td class="value">{$author.affiliation|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.country"}</td>
		<td class="value">{$author.countryLocalized|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.biography"}</td>
		<td class="value">{$author.biography|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
	</tr>
	{if !$smarty.foreach.authors.last}
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{/foreach}
</table>


<div class="separator"></div>
{/if}


<h3>{if $section->getAbstractsDisabled()}{translate key="article.title"}{else}{translate key="submission.titleAndAbstract"}{/if}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.title"}</td>
		<td width="80%" class="value">{$title|escape|default:"&mdash;"}</td>
	</tr>
	{if $alternateLocale1}
	<tr valign="top">
		<td class="label">{translate key="article.title"}<br />({$languageToggleLocales.$alternateLocale1})</td>
		<td class="value">{$titleAlt1|escape|default:"&mdash;"}</td>
	</tr>
	{/if}
	{if $alternateLocale2}
	<tr valign="top">
		<td class="label">{translate key="article.title"}<br />({$languageToggleLocales.$alternateLocale2})</td>
		<td class="value">{$titleAlt2|escape|default:"&mdash;"}</td>
	</tr>
	{/if}

	{if !$section->getAbstractsDisabled()}
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="article.abstract"}</td>
		<td class="value">{$abstract|nl2br|default:"&mdash;"}</td>
	</tr>
	{if $alternateLocale1}
	<tr valign="top">
		<td class="label">{translate key="article.abstract"}<br />({$languageToggleLocales.$alternateLocale1})</td>
		<td class="value">{$abstractAlt1|nl2br|default:"&mdash;"}</td>
	</tr>
	{/if}
	{if $alternateLocale2}
	<tr valign="top">
		<td class="label">{translate key="article.abstract"}<br />({$languageToggleLocales.$alternateLocale2})</td>
		<td class="value">{$abstractAlt2|nl2br|default:"&mdash;"}</td>
	</tr>
	{/if}
	{/if}
</table>


<div class="separator"></div>


<h3>{translate key="submission.indexing"}</h3>
	
<table width="100%" class="data">
	{if $journalSettings.metaDiscipline}
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.discipline"}</td>
		<td width="80%" class="value">{$discipline|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $journalSettings.metaSubjectClass}
	<tr valign="top">
		<td colspan="2" class="label"><a href="{$journalSettings.metaSubjectClassUrl}" target="_blank">{$journalSettings.metaSubjectClassTitle|escape}</a></td>
	</tr>
	<tr valign="top">
		<td width="20%"class="label">{translate key="article.subjectClassification"}</td>
		<td width="80%" class="value">{$subjectClass|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $journalSettings.metaSubject}
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.subject"}</td>
		<td width="80%" class="value">{$subject|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $journalSettings.metaCoverage}
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.coverageGeo"}</td>
		<td width="80%" class="value">{$coverageGeo|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="article.coverageChron"}</td>
		<td class="value">{$coverageChron|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="article.coverageSample"}</td>
		<td class="value">{$coverageSample|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $journalSettings.metaType}
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.type"}</td>
		<td width="80%" class="value">{$type|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.language"}</td>
		<td width="80%" class="value">{$language|default:"&mdash;"}</td>
	</tr>
</table>


<div class="separator"></div>


<h3>{translate key="submission.supportingAgencies"}</h3>
	
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="author.submit.agencies"}</td>
		<td width="80%" class="value">{$sponsor|escape|default:"&mdash;"}</td>
	</tr>
</table>

{include file="common/footer.tpl"}
