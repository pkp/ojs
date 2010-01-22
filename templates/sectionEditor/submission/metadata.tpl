{**
 * metadata.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the submission metadata table.
 *
 * $Id$
 *}
<a name="metadata"></a>
<table class="data">
	<tr valign="middle">
		<td><h3>{translate key="submission.metadata"}</h3></td>
		<td>&nbsp;<br/><a href="{url op="viewMetadata" path=$submission->getArticleId()}" class="action">{translate key="submission.editMetadata"}</a></td>
	</tr>
</table>

<h4>{translate key="article.authors"}</h4>
	
<table width="100%" class="data">
	{foreach name=authors from=$authors item=author}
	<tr valign="top">
		<td width="20%" class="label">{translate key="user.name"}</td>
		<td width="80%" class="value">
			{assign var=emailString value="`$author->getFullName()` <`$author->getEmail()`>"}
			{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$emailString|to_array subject=$submission->getArticleTitle|strip_tags articleId=$submission->getArticleId()}
			{$author->getFullName()|escape} {icon name="mail" url=$url}
		</td>
	</tr>
	{if $author->getEmail()}<tr valign="top">
		<td class="label">{translate key="user.url"}</td>
		<td class="value"><a href="{$author->getUrl()|escape:"quotes"}">{$author->getUrl()|escape}</a></td>
	</tr>{/if}
	<tr valign="top">
		<td class="label">{translate key="user.affiliation"}</td>
		<td class="value">{$author->getAffiliation()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.country"}</td>
		<td class="value">{$author->getCountryLocalized()|escape|default:"&mdash;"}</td>
	</tr>
	{if $currentJournal->getSetting('requireAuthorCompetingInterests')}
	<tr valign="top">
		<td class="label">
			{url|assign:"competingInterestGuidelinesUrl" page="information" op="competingInterestGuidelines"}
			{translate key="author.competingInterests" competingInterestGuidelinesUrl=$competingInterestGuidelinesUrl}
		</td>
		<td class="value">{$author->getAuthorCompetingInterests()|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
	</tr>
	{/if}
	<tr valign="top">
		<td class="label">{translate key="user.biography"}</td>
		<td class="value">{$author->getAuthorBiography()|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
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

<h4>{translate key="submission.titleAndAbstract"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.title"}</td>
		<td width="80%" class="value">{$submission->getArticleTitle()|strip_unsafe_html|default:"&mdash;"}</td>
	</tr>

	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="article.abstract"}</td>
		<td class="value">{$submission->getArticleAbstract()|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
	</tr>
</table>

<h4>{translate key="submission.indexing"}</h4>
	
<table width="100%" class="data">
	{if $journalSettings.metaDiscipline}
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.discipline"}</td>
		<td width="80%" class="value">{$submission->getArticleDiscipline()|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $journalSettings.metaSubjectClass}
	<tr valign="top">
		<td width="20%"  class="label">{translate key="article.subjectClassification"}</td>
		<td width="80%" class="value">{$submission->getArticleSubjectClass()|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $journalSettings.metaSubject}
	<tr valign="top">
		<td width="20%"  class="label">{translate key="article.subject"}</td>
		<td width="80%" class="value">{$submission->getArticleSubject()|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $journalSettings.metaCoverage}
	<tr valign="top">
		<td width="20%"  class="label">{translate key="article.coverageGeo"}</td>
		<td width="80%" class="value">{$submission->getArticleCoverageGeo()|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="article.coverageChron"}</td>
		<td class="value">{$submission->getArticleCoverageChron()|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="article.coverageSample"}</td>
		<td class="value">{$submission->getArticleCoverageSample()|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $journalSettings.metaType}
	<tr valign="top">
		<td width="20%"  class="label">{translate key="article.type"}</td>
		<td width="80%" class="value">{$submission->getArticleType()|escape|default:"&mdash;"}</td>
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

<h4>{translate key="submission.supportingAgencies"}</h4>
	
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="author.submit.agencies"}</td>
		<td width="80%" class="value">{$submission->getArticleSponsor()|escape|default:"&mdash;"}</td>
	</tr>
</table>
