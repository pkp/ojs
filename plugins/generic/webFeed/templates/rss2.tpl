{**
 * plugins/generic/webFeed/templates/rss2.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * RSS 2 feed template
 *
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:cc="http://web.resource.org/cc/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
	<channel>
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
		{if $journal->getPrimaryLocale()}
			<language>{$journal->getPrimaryLocale()|replace:'_':'-'|strip|escape:"html"}</language>
		{/if}

		{if $journal->getLocalizedData('licenseTerms')}
			<copyright>{$journal->getLocalizedData('licenseTerms')|strip|escape:"html"}</copyright>
		{/if}

		{if $journal->getData('contactEmail')}
			<managingEditor>{$journal->getData('contactEmail')|strip|escape:"html"}{if $journal->getData('contactName')} ({$journal->getData('contactName')|strip|escape:"html"}){/if}</managingEditor>
		{/if}

		{if $journal->getData('supportEmail')}
			<webMaster>{$journal->getData('supportEmail')|strip|escape:"html"}{if $journal->getData('contactName')} ({$journal->getData('supportName')|strip|escape:"html"}){/if}</webMaster>
		{/if}

		{if $issue->getDatePublished()}
			{capture assign="datePublished"}{$issue->getDatePublished()|strtotime}{/capture}
			<pubDate>{$smarty.const.DATE_RSS|date:$datePublished}</pubDate>
		{/if}

		{* <lastBuildDate/> *}
		{* <category/> *}
		{* <creativeCommons:license/> *}

		<generator>OJS {$ojsVersion|escape}</generator>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>
		<ttl>60</ttl>

		{foreach name=sections from=$publishedSubmissions item=section key=sectionId}
			{foreach from=$section.articles item=article}
				{assign var=publication value=$article->getCurrentPublication()}
				<item>
					{* required elements *}
					<title>{$article->getLocalizedTitle()|strip|escape:"html"}</title>
					<link>{url page="article" op="view" path=$article->getBestId()}</link>
					<description>{$article->getLocalizedAbstract()|strip|escape:"html"}</description>

					{* optional elements *}
					{* <author/> *}
					<dc:creator>{$article->getAuthorString(false)|escape:"html"}</dc:creator>
					{* <category/> *}
					{* <comments/> *}
					{* <source/> *}

					<dc:rights>
						{translate|escape key="submission.copyrightStatement" copyrightYear=$article->getCopyrightYear() copyrightHolder=$article->getLocalizedCopyrightHolder()}
						{$article->getLicenseURL()|escape}
					</dc:rights>
					{if ($publication->getData('accessStatus') == $smarty.const.ARTICLE_ACCESS_OPEN || ($publication->getData('accessStatus') == $smarty.const.ARTICLE_ACCESS_ISSUE_DEFAULT && $issue->getAccessStatus() == $smarty.const.ISSUE_ACCESS_OPEN)) && $article->isCCLicense()}
						<cc:license rdf:resource="{$article->getLicenseURL()|escape}" />
					{else}
						<cc:license></cc:license>
					{/if}

					<guid isPermaLink="true">{url page="article" op="view" path=$article->getBestId()}</guid>
					{if $article->getDatePublished()}
						{capture assign="datePublished"}{$article->getDatePublished()|strtotime}{/capture}
						<pubDate>{$smarty.const.DATE_RSS|date:$datePublished}</pubDate>
					{/if}
				</item>
			{/foreach}{* articles *}
		{/foreach}{* sections *}
	</channel>
</rss>
