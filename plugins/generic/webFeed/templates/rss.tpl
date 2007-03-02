<?xml version="1.0" encoding="{$defaultCharset}"?>
<rdf:RDF
	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	xmlns="http://purl.org/rss/1.0/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:prism="http://prismstandard.org/namespaces/1.2/basic/">
    
	<channel rdf:about="{$journal->getUrl()}">
		{* required elements *}
		<title>{$journal->getTitle()|escape:"html"|strip|strip_tags}</title>
		<link>{$journal->getUrl()}</link>
		{if $journal->getDescription()}
			{assign var="description" value=$journal->getDescription()}
		{elseif $journal->getSetting('journalDescription')}
			{assign var="description" value=$journal->getSetting('journalDescription')}
		{elseif $journal->getSetting('searchDescription')}
			{assign var="description" value=$journal->getSetting('searchDescription')}
		{/if}
		<description>{$description|escape:"html"|strip|strip_tags}</description>

		{* optional elements *}
		{assign var="publisher" value=$journal->getSetting('publisher')}
		{if $publisher.institution}
		<dc:publisher>{$publisher.institution|escape:"html"|strip|strip_tags}</dc:publisher>
		{/if}
		{if $journal->getLocale()}
		<dc:language>{$journal->getLocale()|replace:'_':'-'|escape:"html"|strip|strip_tags}</dc:language>
		{/if}
		<prism:publicationName>{$journal->getTitle()|escape:"html"|strip|strip_tags}</prism:publicationName>

		{if $journal->getSetting('printIssn')}
			{assign var="ISSN" value=$journal->getSetting('printIssn')}
		{elseif $journal->getSetting('issn')}
			{assign var="ISSN" value=$journal->getSetting('issn')}
		{elseif $journal->getSetting('onlineIssn')}
			{assign var="ISSN" value=$journal->getSetting('onlineIssn')}
		{/if}
		{if $ISSN}
		<prism:issn>{$ISSN}</prism:issn>
		{/if}
		{if $journal->getSetting('copyrightNotice')}
		<prism:copyright>{$journal->getSetting('copyrightNotice')|escape:"html"|strip|strip_tags}</prism:copyright>
		{/if}

		<items>
			<rdf:Seq>
			{foreach name=sections from=$publishedArticles item=section key=sectionId}
			{foreach from=$section.articles item=article}
				<rdf:li rdf:resource="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}"/>
			{/foreach}
			{/foreach}
			</rdf:Seq>
		</items>
	</channel>

{foreach name=sections from=$publishedArticles item=section key=sectionId}
{foreach from=$section.articles item=article}
	<item rdf:about="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}">
		{* required elements *}
		<title>{$article->getTitle()|escape:"html"|strip|strip_tags}</title>
		<link>{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}</link>

		{* optional elements *}
		{if $article->getAbstract()}
		<description>{$article->getAbstract()|escape:"html"|strip|strip_tags}</description>
		{/if}
		{foreach from=$article->getAuthors() item=author name=authorList}
		<dc:creator>{$author->getFullName()|escape:"html"|strip|strip_tags}</dc:creator>
		{/foreach}
		<dc:date>{$article->getDatePublished()|date_format:"%Y-%m-%d"}</dc:date>
		<prism:volume>{$issue->getVolume()}</prism:volume>
		<prism:publicationDate>{$article->getDatePublished()|date_format:"%Y-%m-%d"}</prism:publicationDate>
	</item>
{/foreach}
{/foreach}

</rdf:RDF>

