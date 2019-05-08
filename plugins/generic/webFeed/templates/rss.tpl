{**
 * plugins/generic/webFeed/templates/rss.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
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
	xmlns:prism="http://prismstandard.org/namespaces/1.2/basic/"
	xmlns:cc="http://web.resource.org/cc/">

	<channel rdf:about="{url journal=$journal->getPath()}">
		{* required elements *}
		<title>{$journal->getLocalizedName()|strip|escape:"html"}</title>
		<link>{url journal=$journal->getPath()}</link>

		{if $journal->getLocalizedDescription()}
			{assign var="description" value=$journal->getLocalizedDescription()}
		{elseif $journal->getLocalizedData('searchDescription')}
			{assign var="description" value=$journal->getLocalizedData('searchDescription')}
		{/if}

		<description>{$description|strip|escape:"html"}</description>

		{* optional elements *}
		{assign var="publisherInstitution" value=$journal->getData('publisherInstitution')}
		{if $publisherInstitution}
			<dc:publisher>{$publisherInstitution|strip|escape:"html"}</dc:publisher>
		{/if}

		{if $journal->getPrimaryLocale()}
			<dc:language>{$journal->getPrimaryLocale()|replace:'_':'-'|strip|escape:"html"}</dc:language>
		{/if}

		<prism:publicationName>{$journal->getLocalizedName()|strip|escape:"html"}</prism:publicationName>

		{if $journal->getData('printIssn')}
			{assign var="ISSN" value=$journal->getData('printIssn')}
		{elseif $journal->getData('onlineIssn')}
			{assign var="ISSN" value=$journal->getData('onlineIssn')}
		{/if}

		{if $ISSN}
			<prism:issn>{$ISSN|escape}</prism:issn>
		{/if}

		{if $journal->getLocalizedData('licenseTerms')}
			<prism:copyright>{$journal->getLocalizedData('licenseTerms')|strip|escape:"html"}</prism:copyright>
		{/if}

		<items>
			<rdf:Seq>
			{foreach name=sections from=$publishedArticles item=section key=sectionId}
				{foreach from=$section.articles item=article}
					<rdf:li rdf:resource="{url page="article" op="view" path=$article->getBestArticleId()}"/>
				{/foreach}{* articles *}
			{/foreach}{* sections *}
			</rdf:Seq>
		</items>
	</channel>

{foreach name=sections from=$publishedArticles item=section key=sectionId}
	{foreach from=$section.articles item=article}
		<item rdf:about="{url page="article" op="view" path=$article->getBestArticleId()}">

			{* required elements *}
			<title>{$article->getLocalizedTitle()|strip|escape:"html"}</title>
			<link>{url page="article" op="view" path=$article->getBestArticleId()}</link>

			{* optional elements *}
			{if $article->getLocalizedAbstract()}
				<description>{$article->getLocalizedAbstract()|strip|escape:"html"}</description>
			{/if}

			{foreach from=$article->getAuthors() item=author name=authorList}
				<dc:creator>{$author->getFullName(false)|strip|escape:"html"}</dc:creator>
			{/foreach}

			<dc:rights>
				{translate|escape key="submission.copyrightStatement" copyrightYear=$article->getCopyrightYear() copyrightHolder=$article->getLocalizedCopyrightHolder()}
				{$article->getLicenseURL()|escape}
			</dc:rights>
			{if ($article->getAccessStatus() == $smarty.const.ARTICLE_ACCESS_OPEN || ($article->getAccessStatus() == $smarty.const.ARTICLE_ACCESS_ISSUE_DEFAULT && $issue->getAccessStatus() == $smarty.const.ISSUE_ACCESS_OPEN)) && $article->isCCLicense()}
				<cc:license rdf:resource="{$article->getLicenseURL()|escape}" />
			{else}
				<cc:license></cc:license>
			{/if}

			{if $article->getDatePublished()}
				<dc:date>{$article->getDatePublished()|date_format:"%Y-%m-%d"}</dc:date>
				<prism:publicationDate>{$article->getDatePublished()|date_format:"%Y-%m-%d"}</prism:publicationDate>
			{/if}
			{if $issue->getVolume() && $issue->getShowVolume()}<prism:volume>{$issue->getVolume()|escape}</prism:volume>{/if}
			{if $issue->getNumber() && $issue->getShowNumber()}<prism:number>{$issue->getNumber()|escape}</prism:number>{/if}

			{if $article->getPages()}
				{if $article->getStartingPage()}
					<prism:startingPage>{$article->getStartingPage()|escape}</prism:startingPage>
				{/if}
				{if $article->getEndingPage()}
					<prism:endingPage>{$article->getEndingPage()|escape}</prism:endingPage>
				{/if}
			{/if}

			{if $article->getStoredPubId('doi')}
				<prism:doi>{$article->getStoredPubId('doi')|escape}</prism:doi>
			{/if}
		</item>
	{/foreach}{* articles *}
{/foreach}{* sections *}

</rdf:RDF>
