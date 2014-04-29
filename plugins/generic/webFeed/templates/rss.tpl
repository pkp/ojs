{**
 * plugins/generic/webFeed/templates/rss.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
		<title>{$journal->getLocalizedTitle()|strip|escape:"html"}</title>
		<link>{$journal->getUrl()|escape}</link>

		{if $journal->getLocalizedDescription()}
			{assign var="description" value=$journal->getLocalizedDescription()}
		{elseif $journal->getLocalizedSetting('searchDescription')}
			{assign var="description" value=$journal->getLocalizedSetting('searchDescription')}
		{/if}

		<description>{$description|strip|escape:"html"}</description>

		{* optional elements *}
		{assign var="publisherInstitution" value=$journal->getSetting('publisherInstitution')}
		{if $publisherInstitution}
			<dc:publisher>{$publisherInstitution|strip|escape:"html"}</dc:publisher>
		{/if}

		{if $journal->getPrimaryLocale()}
			<dc:language>{$journal->getPrimaryLocale()|replace:'_':'-'|strip|escape:"html"}</dc:language>
		{/if}

		<prism:publicationName>{$journal->getLocalizedTitle()|strip|escape:"html"}</prism:publicationName>

		{if $journal->getSetting('printIssn')}
			{assign var="ISSN" value=$journal->getSetting('printIssn')}
		{elseif $journal->getSetting('onlineIssn')}
			{assign var="ISSN" value=$journal->getSetting('onlineIssn')}
		{/if}

		{if $ISSN}
			<prism:issn>{$ISSN|escape}</prism:issn>
		{/if}

		{if $journal->getLocalizedSetting('copyrightNotice')}
			<prism:copyright>{$journal->getLocalizedSetting('copyrightNotice')|strip|escape:"html"}</prism:copyright>
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
			{if $issue->getVolume()}<prism:volume>{$issue->getVolume()|escape}</prism:volume>{/if}
			{if $issue->getNumber()}<prism:number>{$issue->getNumber()|escape}</prism:number>{/if}

			{if $article->getPages()}
				{if $article->getStartingPage()}
					<prism:startingPage>{$article->getStartingPage()|escape}</prism:startingPage>
				{/if}
				{if $article->getEndingPage()}
					<prism:endingPage>{$article->getEndingPage()|escape}</prism:endingPage>
				{/if}
			{/if}

			{if $article->getPubId('doi')}
				<prism:doi>{$article->getPubId('doi')|escape}</prism:doi>
			{/if}
		</item>
	{/foreach}{* articles *}
{/foreach}{* sections *}

</rdf:RDF>

