{**
 * rss2.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RSS 2 feed template
 *
 * $Id$
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<rss version="2.0">
	<channel>
		{* required elements *}
		<title>{$journal->getJournalTitle()|escape:"html"|strip}</title>
		<link>{$journal->getUrl()}</link>

		{if $journal->getJournalDescription()}
			{assign var="description" value=$journal->getJournalDescription()}
		{elseif $journal->getLocalizedSetting('searchDescription')}
			{assign var="description" value=$journal->getLocalizedSetting('searchDescription')}
		{/if}

		<description>{$description|strip|escape:"html"}</description>

		{* optional elements *}
		{if $journal->getPrimaryLocale()}
			<language>{$journal->getPrimaryLocale()|replace:'_':'-'|strip|escape:"html"}</language>
		{/if}

		{if $journal->getLocalizedSetting('copyrightNotice')}
			<copyright>{$journal->getLocalizedSetting('copyrightNotice')|strip|escape:"html"}</copyright>
		{/if}

		{if $journal->getSetting('contactEmail')}
			<managingEditor>{$journal->getSetting('contactEmail')|strip|escape:"html"}{if $journal->getSetting('contactName')} ({$journal->getSetting('contactName')|strip|escape:"html"}){/if}</managingEditor>
		{/if}

		{if $journal->getSetting('supportEmail')}
			<webMaster>{$journal->getSetting('supportEmail')|strip|escape:"html"}{if $journal->getSetting('contactName')} ({$journal->getSetting('supportName')|strip|escape:"html"}){/if}</webMaster>
		{/if}

		<pubDate>{$issue->getDatePublished()|date_format:"%a, %d %b %Y %T %z"}</pubDate>

		{* <lastBuildDate/> *}
		{* <category/> *}
		{* <creativeCommons:license/> *}

		<generator>OJS {$ojsVersion|escape}</generator>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>
		<ttl>60</ttl>

		{foreach name=sections from=$publishedArticles item=section key=sectionId}
			{foreach from=$section.articles item=article}
				<item>
					{* required elements *}
					<title>{$article->getArticleTitle()|strip|escape:"html"}</title>
					<link>{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}</link>
					<description>{$article->getArticleAbstract()|strip|escape:"html"}</description>

					{* optional elements *}
					<author>{$article->getAuthorString()|escape:"html"}</author>
					{* <category/> *}
					{* <comments/> *}
					{* <source/> *}

					<guid isPermaLink="true">{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}</guid>
					<pubDate>{$article->getDatePublished()|date_format:"%a, %d %b %Y %T %z"}</pubDate>
				</item>
			{/foreach}{* articles *}
		{/foreach}{* sections *}
	</channel>
</rss>
