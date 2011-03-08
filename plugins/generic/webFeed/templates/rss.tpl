{**
 * rss.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
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

	<channel rdf:about="{$journal->getUrl()|escape}">
		{* required elements *}
		<title>{$journal->getLocalizedTitle()|escape:"html"|strip}</title>
		<link>{$journal->getUrl()|escape}</link>

		{if $journal->getLocalizedDescription()}
			{assign var="description" value=$journal->getLocalizedDescription()}
		{elseif $journal->getLocalizedSetting('searchDescription')}
			{assign var="description" value=$journal->getLocalizedSetting('searchDescription')}
		{/if}

		<description>{$description|escape:"html"|strip}</description>

		{* optional elements *}
		{assign var="publisherInstitution" value=$journal->getSetting('publisherInstitution')}
		{if $publisherInstitution}
			<dc:publisher>{$publisherInstitution|escape:"html"|strip}</dc:publisher>
		{/if}

		{if $journal->getPrimaryLocale()}
			<dc:language>{$journal->getPrimaryLocale()|replace:'_':'-'|escape:"html"|strip}</dc:language>
		{/if}

		<prism:publicationName>{$journal->getLocalizedTitle()|escape:"html"|strip}</prism:publicationName>

		{if $journal->getSetting('printIssn')}
			{assign var="ISSN" value=$journal->getSetting('printIssn')}
		{elseif $journal->getSetting('onlineIssn')}
			{assign var="ISSN" value=$journal->getSetting('onlineIssn')}
		{/if}

		{if $ISSN}
			<prism:issn>{$ISSN|escape}</prism:issn>
		{/if}

		{if $journal->getLocalizedSetting('copyrightNotice')}
			<prism:copyright>{$journal->getLocalizedSetting('copyrightNotice')|escape:"html"|strip}</prism:copyright>
		{/if}

		<items>
			<rdf:Seq>
			{foreach name=sections from=$publishedArticles item=section key=sectionId}
				{foreach from=$section.articles item=article}
					<rdf:li rdf:resource="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}"/>
				{/foreach}{* articles *}
			{/foreach}{* sections *}
			</rdf:Seq>
		</items>
	</channel>

{foreach name=sections from=$publishedArticles item=section key=sectionId}
	{foreach from=$section.articles item=article}
		<item rdf:about="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}">

			{* required elements *}
			<title>{$article->getLocalizedTitle()|strip|escape:"html"}</title>
			<link>{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}</link>

			{* optional elements *}
			{if $article->getLocalizedAbstract()}
				<description>{$article->getLocalizedAbstract()|strip|escape:"html"}</description>
			{/if}

			{foreach from=$article->getAuthors() item=author name=authorList}
				<dc:creator>{$author->getFullName()|strip|escape:"html"}</dc:creator>
			{/foreach}

			{if $article->getDatePublished()}
				<dc:date>{$article->getDatePublished()|date_format:"%Y-%m-%d"}</dc:date>
				<prism:publicationDate>{$article->getDatePublished()|date_format:"%Y-%m-%d"}</prism:publicationDate>
			{/if}
			<prism:volume>{$issue->getVolume()|escape}</prism:volume>
		</item>
	{/foreach}{* articles *}
{/foreach}{* sections *}

</rdf:RDF>

