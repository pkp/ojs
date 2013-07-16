{**
 * metadata.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
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
	
<table width="100%" class="data">
	{foreach name=authors from=$submission->getAuthors() item=author}
	<tr valign="top">
		<td width="20%" class="label">{translate key="user.name"}</td>
		<td width="80%" class="value">
			{assign var=missingEmail value=0}
			{if $author->getEmail() == ""}
				{assign var=missingEmail value=1}
			{/if}
			{if $missingEmail}
				{assign var=emailString value=""}
			{else}
				{assign var=emailString value=$author->getFullName()|concat:" <":$author->getEmail():">"}
			{/if}
			{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$emailString|to_array subject=$submission->getLocalizedTitle()|strip_tags articleId=$submission->getId()}
			{$author->getFullName()|escape} {icon name="mail" url=$url}
		 	{if $missingEmail}<font color="red">(Note: author has no email address on file.)</font>{/if}	
		</td>
	</tr>
	{if $author->getUrl()}
		<tr valign="top">
			<td class="label">{translate key="user.url"}</td>
			<td class="value"><a href="{$author->getUrl()|escape:"quotes"}">{$author->getUrl()|escape}</a></td>
		</tr>
	{/if}
	<tr valign="top">
		<td class="label">{translate key="user.affiliation"}</td>
		<td class="value">{$author->getLocalizedAffiliation()|escape|nl2br|default:"&mdash;"}</td>
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
			<td class="value">{$author->getLocalizedCompetingInterests()|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
		</tr>
	{/if}
	<tr valign="top">
		<td class="label">{translate key="user.biography"}</td>
		<td class="value">{$author->getLocalizedBiography()|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
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
</div>

<div id="titleAndAbstract">
<h4>{translate key="submission.titleAndAbstract"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.title"}</td>
		<td width="80%" class="value">{$submission->getLocalizedTitle()|strip_unsafe_html|default:"&mdash;"}</td>
	</tr>

	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="article.abstract"}</td>
		<td class="value">{$submission->getLocalizedAbstract()|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
	</tr>
</table>
</div>

<div id="indexing">
<h4>{translate key="submission.indexing"}</h4>
	
<table width="100%" class="data">
	{if $currentJournal->getSetting('metaDiscipline')}
		<tr valign="top">
			<td width="20%" class="label">{translate key="article.eScholdiscipline"}</td>
			{assign var="choices" value=$submission->getLocalizedDiscipline()}			
			<td width="80%" class="value">		
			{foreach from=$choices item=disc}			 
			     {if $disc == 'disc2932'}
			     Architecture<br/>
			     {elseif $disc == 'disc1481'}
			     Arts and Humanities<br/>
			     {elseif $disc == 'disc3688'}
			     Business<br/>
			 {elseif $disc == 'disc3579'}
			 Education<br/>
			 {elseif $disc == 'disc3525'}
			 Engineering<br/>
			 {elseif $disc == 'disc1573'}
			 Law<br/>
			 {elseif $disc == 'disc1540'}
			 Life Sciences<br/>
			 {elseif $disc == 'disc3864'}
			 Physical Sciences and Mathematics<br/>
			{elseif $disc == 'disc1965'}
			 Social and Behavioral Sciences<br/>
			 {/if}
			 {/foreach}
			 </td>
		</tr>
		<tr>
			<td colspan="2" class="separator">&nbsp;</td>
		</tr>
	{/if}
	{if $currentJournal->getSetting('metaSubjectClass')}
		<tr valign="top">
			<td width="20%" class="label">{translate key="article.subjectClassification"}</td>
			<td width="80%" class="value">{$submission->getLocalizedSubjectClass()|escape|default:"&mdash;"}</td>
		</tr>
		<tr>
			<td colspan="2" class="separator">&nbsp;</td>
		</tr>
	{/if}
	{if $currentJournal->getSetting('metaSubject')}
		<tr valign="top">
			<td width="20%" class="label">{translate key="article.subject"}</td>
			<td width="80%" class="value">{$submission->getLocalizedSubject()|escape|default:"&mdash;"}</td>
		</tr>
		<tr>
			<td colspan="2" class="separator">&nbsp;</td>
		</tr>
	{/if}
	{if $currentJournal->getSetting('metaCoverage')}
		<tr valign="top">
			<td width="20%" class="label">{translate key="article.coverageGeo"}</td>
			<td width="80%" class="value">{$submission->getLocalizedCoverageGeo()|escape|default:"&mdash;"}</td>
		</tr>
		<tr>
			<td colspan="2" class="separator">&nbsp;</td>
		</tr>
		<tr valign="top">
			<td class="label">{translate key="article.coverageChron"}</td>
			<td class="value">{$submission->getLocalizedCoverageChron()|escape|default:"&mdash;"}</td>
		</tr>
		<tr>
			<td colspan="2" class="separator">&nbsp;</td>
		</tr>
		<tr valign="top">
			<td class="label">{translate key="article.coverageSample"}</td>
			<td class="value">{$submission->getLocalizedCoverageSample()|escape|default:"&mdash;"}</td>
		</tr>
		<tr>
			<td colspan="2" class="separator">&nbsp;</td>
		</tr>
	{/if}
	{if $currentJournal->getSetting('metaType')}
		<tr valign="top">
			<td width="20%" class="label">{translate key="article.type"}</td>
			<td width="80%" class="value">{$submission->getLocalizedType()|escape|default:"&mdash;"}</td>
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
</div>

<div id="supportingAgencies">
<h4>{translate key="submission.supportingAgencies"}</h4>
	
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="submission.agencies"}</td>
		<td width="80%" class="value">{$submission->getLocalizedSponsor()|escape|default:"&mdash;"}</td>
	</tr>
</table>
</div>

{call_hook name="Templates::Submission::Metadata::Metadata::AdditionalMetadata"}

{if $currentJournal->getSetting('metaCitations')}
	<div id="citations">
	<h4>{translate key="submission.citations"}</h4>

	<table width="100%" class="data">
		<tr valign="top">
			<td width="20%" class="label">{translate key="submission.citations"}</td>
			<td width="80%" class="value">{$submission->getCitations()|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
		</tr>
	</table>
	</div>
{/if}

</div><!-- metadata -->

