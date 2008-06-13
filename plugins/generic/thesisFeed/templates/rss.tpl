{**
 * rss.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RSS feed template
 *
 * $Id$
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<rdf:RDF
	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	xmlns="http://purl.org/rss/1.0/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:prism="http://prismstandard.org/namespaces/1.2/basic/">
    
	<channel rdf:about="{$journal->getUrl()}">
		{* required elements *}
		<title>{$journal->getJournalTitle()|escape:"html"|strip}: {translate key="plugins.generic.thesis.manager.theses"}</title>
		<link>{$journal->getUrl()}</link>
		{if $journal->getJournalDescription()}
			{assign var="description" value=$journal->getJournalDescription()}
		{elseif $journal->getLocalizedSetting('searchDescription')}
			{assign var="description" value=$journal->getLocalizedSetting('searchDescription')}
		{/if}
		<description>{$description|escape:"html"|strip}</description>

		{* optional elements *}
		{if $journal->getPrimaryLocale()}
		<dc:language>{$journal->getPrimaryLocale()|replace:'_':'-'|escape:"html"|strip}</dc:language>
		{/if}

		<items>
			{foreach from=$theses item=thesis}
			<rdf:Seq>
				<rdf:li rdf:resource="{url page="thesis" op="view" path=$thesis->getThesisId()}"/>
			</rdf:Seq>
			{/foreach}
		</items>
	</channel>

{assign var="break" value="<br />"|escape:"html"}
{assign var="urlOpen" value="<a href=\"URL\">"}
{assign var="urlClose" value="</a>"|escape:html}

{foreach from=$theses item=thesis}
	<item rdf:about="{url page="thesis" op="view" path=$thesis->getThesisId()}">
		{* required elements *}
		<title>{$thesis->getTitle()|strip|escape:"html"}</title>
		<link>{url page="thesis" op="view" path=$thesis->getThesisId()}</link>

		{if $thesis->getUrl()}
			{assign var="thesisUrlOpen" value=$urlOpen|replace:"URL":$thesis->getUrl()|escape:"html"}
		{else}
			{assign var="thesisUrlOpen" value=""}
		{/if}

		{* optional elements *}
		<description>{$thesis->getDepartment()|strip|escape:"html"}, {$thesis->getUniversity()|strip|escape:"html"}{$break}{$thesis->getDateApproved()|date_format:"%B, %Y"}{$break}{$break}{if $thesisUrlOpen}{$thesisUrlOpen}{translate key="plugins.generic.thesis.fullText"}{$urlClose}{$break}{$break}{/if}{$thesis->getAbstract()|strip|escape:"html"}</description>
		<dc:creator>{$journal->getJournalTitle()|strip|escape:"html"}</dc:creator>
		<dc:date>{$thesis->getDateSubmitted()|date_format:"%Y-%m-%d"}</dc:date>
	</item>
{/foreach}

</rdf:RDF>
