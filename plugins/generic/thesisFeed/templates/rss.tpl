{**
 * plugins/generic/thesisFeed/templates/rss.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RSS feed template
 *
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<rdf:RDF
	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	xmlns="http://purl.org/rss/1.0/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:prism="http://prismstandard.org/namespaces/1.2/basic/">

	<channel rdf:about="{$journal->getUrl()|escape}">
		{* required elements *}
		<title>{$journal->getLocalizedTitle()|strip|escape:"html"}: {translate key="plugins.generic.thesis.manager.theses"}</title>
		<link>{$journal->getUrl()|escape}</link>
		{if $journal->getLocalizedDescription()}
			{assign var="description" value=$journal->getLocalizedDescription()}
		{elseif $journal->getLocalizedSetting('searchDescription')}
			{assign var="description" value=$journal->getLocalizedSetting('searchDescription')}
		{/if}
		<description>{$description|strip|escape:"html"}</description>

		{* optional elements *}
		{if $journal->getPrimaryLocale()}
		<dc:language>{$journal->getPrimaryLocale()|replace:'_':'-'|strip|escape:"html"}</dc:language>
		{/if}

		<items>
			{foreach from=$theses item=thesis}
			<rdf:Seq>
				<rdf:li rdf:resource="{url page="thesis" op="view" path=$thesis->getId()}"/>
			</rdf:Seq>
			{/foreach}
		</items>
	</channel>

{assign var="break" value="<br />"|escape:"html"}
{assign var="urlOpen" value="<a href=\"URL\">"}
{assign var="urlClose" value="</a>"|escape:html}

{foreach from=$theses item=thesis}
	<item rdf:about="{url page="thesis" op="view" path=$thesis->getId()}">
		{* required elements *}
		<title>{$thesis->getTitle()|strip|escape:"html"}</title>
		<link>{url page="thesis" op="view" path=$thesis->getId()}</link>

		{if $thesis->getUrl()}
			{assign var="thesisUrlOpen" value=$urlOpen|replace:"URL":$thesis->getUrl()|escape:"html"}
		{else}
			{assign var="thesisUrlOpen" value=""}
		{/if}

		{* optional elements *}
		<description>{$thesis->getDepartment()|strip|escape:"html"}, {$thesis->getUniversity()|strip|escape:"html"}{$break}{$thesis->getDateApproved()|date_format:"%B, %Y"}{$break}{$break}{if $thesisUrlOpen}{$thesisUrlOpen}{translate key="plugins.generic.thesis.fullText"}{$urlClose}{$break}{$break}{/if}{$thesis->getAbstract()|strip|escape:"html"}</description>
		<dc:creator>{$journal->getLocalizedTitle()|strip|escape:"html"}</dc:creator>
		<dc:date>{$thesis->getDateSubmitted()|date_format:"%Y-%m-%d"}</dc:date>
	</item>
{/foreach}

</rdf:RDF>
