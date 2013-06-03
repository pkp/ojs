{**
 * metadata_view.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * View (but not edit) metadata of an article. Used by MetadataForm.
 *
 *}
{strip}
{assign var="pageTitle" value="submission.viewMetadata"}
{include file="common/header.tpl"}
{/strip}

{if $canViewAuthors}
	{url|assign:authorGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.users.author.AuthorGridHandler" op="fetchGrid" articleId=$articleId escape=false}
	{load_url_in_div id="authorsGridContainer" url="$authorGridUrl"}
</div>

<div class="separator"></div>
{/if}

<div id="titleAndAbstract">
<h3>{translate key="submission.titleAndAbstract"}</h3>

<table class="data">
	<tr>
		<td class="label">{translate key="article.title"}</td>
		<td class="value">{$title[$formLocale]|strip_unsafe_html|default:"&mdash;"}</td>
	</tr>

	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td class="label">{translate key="article.abstract"}</td>
		<td class="value">{$abstract[$formLocale]|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
	</tr>
</table>
</div>
<div class="separator"></div>
<div id="cover">
<h3>{translate key="editor.article.cover"}</h3>

<table class="data">
	<tr>
		<td class="label">{fieldLabel name="coverPage" key="editor.article.coverPage"}</td>
		<td class="value">{if $fileName[$formLocale]}<a href="javascript:openWindow('{$publicFilesDir}/{$fileName[$formLocale]|escape:"url"}');" class="file">{$originalFileName[$formLocale]}</a>{else}&mdash;{/if}</td>
	</tr>

	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="coverPageAltText" key="common.altText"}</td>
		<td class="value">{$coverPageAltText[$formLocale]|escape}</td>
	</tr>
</table>
</div>
<div class="separator"></div>
<div id="indexing">
<h3>{translate key="submission.indexing"}</h3>

<table class="data">
	{if $currentJournal->getSetting('metaDiscipline')}
	<tr>
		<td class="label">{translate key="article.discipline"}</td>
		<td class="value">{$discipline[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $currentJournal->getSetting('metaSubjectClass')}
	<tr>
		<td colspan="2" class="label"><a href="{$currentJournal->getLocalizedSetting('metaSubjectClassUrl')|escape}" target="_blank">{$currentJournal->getLocalizedSetting('metaSubjectClassTitle')|escape}</a></td>
	</tr>
	<tr>
		<tdclass="label">{translate key="article.subjectClassification"}</td>
		<td class="value">{$subjectClass[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	<tr>
		<td class="label">{translate key="article.subject"}</td>
		<td class="value">{$subject[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{if $currentJournal->getSetting('metaCoverage')}
	<tr>
		<td class="label">{translate key="article.coverageGeo"}</td>
		<td class="value">{$coverageGeo[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td class="label">{translate key="article.coverageChron"}</td>
		<td class="value">{$coverageChron[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td class="label">{translate key="article.coverageSample"}</td>
		<td class="value">{$coverageSample[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $currentJournal->getSetting('metaType')}
	<tr>
		<td class="label">{translate key="article.type"}</td>
		<td class="value">{$type[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	<tr>
		<td class="label">{translate key="article.language"}</td>
		<td class="value">{$language|escape|default:"&mdash;"}</td>
	</tr>
</table>
</div>

<div class="separator"></div>

<div id="supportingAgencies">
<h3>{translate key="submission.supportingAgencies"}</h3>

<table class="data">
	<tr>
		<td class="label">{translate key="submission.agencies"}</td>
		<td class="value">{$sponsor[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
</table>
</div>

{include file="common/footer.tpl"}

