{**
 * templates/submission/metadata/metadata.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the submission metadata table. Non-form implementation.
 *}
<div id="metadata">
<h3>{translate key="submission.metadata"}</h3>

{if $canEditMetadata}
	<p><a href="{url op="viewMetadata" path=$submission->getId()}" class="action">{translate key="submission.editMetadata"}</a></p>
	{call_hook name="Templates::Submission::Metadata::Metadata::AdditionalEditItems"}
{/if}

<div id="authors">
<h4>{translate key="article.authors"}</h4>

<table class="data">
	{foreach name=authors from=$submission->getAuthors() item=author}
	<tr>
		<td class="label">{translate key="user.name"}</td>
		<td class="value">
			{assign var=emailString value=$author->getFullName()|concat:" <":$author->getEmail():">"}
			{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$emailString|to_array subject=$submission->getLocalizedTitle()|strip_tags articleId=$submission->getId()}
			{$author->getFullName()|escape} {icon name="mail" url=$url}
		</td>
	</tr>
	{if $author->getUrl()}
		<tr>
			<td class="label">{translate key="user.url"}</td>
			<td class="value"><a href="{$author->getUrl()|escape:"quotes"}">{$author->getUrl()|escape}</a></td>
		</tr>
	{/if}
	<tr>
		<td class="label">{translate key="user.affiliation"}</td>
		<td class="value">{$author->getLocalizedAffiliation()|escape|nl2br|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td class="label">{translate key="common.country"}</td>
		<td class="value">{$author->getCountryLocalized()|escape|default:"&mdash;"}</td>
	</tr>
	{if $currentJournal->getSetting('requireAuthorCompetingInterests')}
		<tr>
			<td class="label">
				{url|assign:"competingInterestGuidelinesUrl" page="information" op="competingInterestGuidelines"}
				{translate key="author.competingInterests" competingInterestGuidelinesUrl=$competingInterestGuidelinesUrl}
			</td>
			<td class="value">{$author->getLocalizedCompetingInterests()|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
		</tr>
	{/if}
	<tr>
		<td class="label">{translate key="user.biography"}</td>
		<td class="value">{$author->getLocalizedBiography()|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
	</tr>
	{if $author->getPrimaryContact()}
		<tr>
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
</div>

<div id="titleAndAbstract">
<h4>{translate key="submission.titleAndAbstract"}</h4>

<table class="data">
	<tr>
		<td class="label">{translate key="article.title"}</td>
		<td class="value">{$submission->getLocalizedFullTitle()|strip_unsafe_html|default:"&mdash;"}</td>
	</tr>

	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td class="label">{translate key="article.abstract"}</td>
		<td class="value">{$submission->getLocalizedAbstract()|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
	</tr>
</table>
</div>

<div id="indexing">
<h4>{translate key="submission.indexing"}</h4>

<table class="data">
	{if $currentJournal->getSetting('metaDiscipline')}
		<tr>
			<td class="label">{translate key="article.discipline"}</td>
			<td class="value">{$submission->getLocalizedDiscipline()|escape|default:"&mdash;"}</td>
		</tr>
		<tr>
			<td colspan="2" class="separator">&nbsp;</td>
		</tr>
	{/if}
	{if $currentJournal->getSetting('metaSubjectClass')}
		<tr>
			<td class="label">{translate key="article.subjectClassification"}</td>
			<td class="value">{$submission->getLocalizedSubjectClass()|escape|default:"&mdash;"}</td>
		</tr>
		<tr>
			<td colspan="2" class="separator">&nbsp;</td>
		</tr>
	{/if}
		<tr>
			<td class="label">{translate key="article.subject"}</td>
			<td class="value">{$submission->getLocalizedSubject()|escape|default:"&mdash;"}</td>
		</tr>
		<tr>
			<td colspan="2" class="separator">&nbsp;</td>
		</tr>
	{if $currentJournal->getSetting('metaCoverage')}
		<tr>
			<td class="label">{translate key="article.coverageGeo"}</td>
			<td class="value">{$submission->getLocalizedCoverageGeo()|escape|default:"&mdash;"}</td>
		</tr>
		<tr>
			<td colspan="2" class="separator">&nbsp;</td>
		</tr>
		<tr>
			<td class="label">{translate key="article.coverageChron"}</td>
			<td class="value">{$submission->getLocalizedCoverageChron()|escape|default:"&mdash;"}</td>
		</tr>
		<tr>
			<td colspan="2" class="separator">&nbsp;</td>
		</tr>
		<tr>
			<td class="label">{translate key="article.coverageSample"}</td>
			<td class="value">{$submission->getLocalizedCoverageSample()|escape|default:"&mdash;"}</td>
		</tr>
		<tr>
			<td colspan="2" class="separator">&nbsp;</td>
		</tr>
	{/if}
	{if $currentJournal->getSetting('metaType')}
		<tr>
			<td class="label">{translate key="article.type"}</td>
			<td class="value">{$submission->getLocalizedType()|escape|default:"&mdash;"}</td>
		</tr>
		<tr>
			<td colspan="2" class="separator">&nbsp;</td>
		</tr>
	{/if}
	<tr>
		<td class="label">{translate key="article.language"}</td>
		<td class="value">{$submission->getLanguage()|escape|default:"&mdash;"}</td>
	</tr>
</table>
</div>

<div id="supportingAgencies">
<h4>{translate key="submission.supportingAgencies"}</h4>

<table class="data">
	<tr>
		<td class="label">{translate key="submission.agencies"}</td>
		<td class="value">{$submission->getLocalizedSponsor()|escape|default:"&mdash;"}</td>
	</tr>
</table>
</div>

{call_hook name="Templates::Submission::Metadata::Metadata::AdditionalMetadata"}

{if $currentJournal->getSetting('metaCitations')}
	<div id="citations">
	<h4>{translate key="submission.citations"}</h4>

	<table class="data">
		<tr>
			<td class="label">{translate key="submission.citations"}</td>
			<td class="value">{$submission->getCitations()|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
		</tr>
	</table>
	</div>
{/if}

</div><!-- metadata -->

