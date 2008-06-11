{**
 * atom.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Atom feed template
 *
 * $Id$
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<feed xmlns="http://www.w3.org/2005/Atom">
	{* required elements *}
	<id>{$journal->getUrl()}/issue/feed</id>
	<title>{$journal->getJournalTitle()|escape:"html"|strip}</title>
	<updated>{$issue->getDatePublished()|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</updated>

	{* recommended elements *}
	{if $journal->getSetting('contactName')}
		<author>
			<name>{$journal->getSetting('contactName')|escape:"html"|strip}</name>
			{if $journal->getSetting('contactEmail')}
			<email>{$journal->getSetting('contactEmail')|escape:"html"|strip}</email>
			{/if}
		</author>
	{/if}

	<link rel="alternate" href="{$journal->getUrl()}" />
	<link rel="self" type="application/atom+xml" href="{$journal->getUrl()}/feed/atom" />

	{* optional elements *}

	{* <category/> *}
	{* <contributor/> *}

	<generator uri="http://pkp.sfu.ca/ojs/" version="{$ojsVersion|escape}">Open Journal Systems</generator>
	{if $journal->getJournalDescription()}
		{assign var="description" value=$journal->getJournalDescription()}
	{elseif $journal->getLocalizedSetting('searchDescription')}
		{assign var="description" value=$journal->getLocalizedSetting('searchDescription')}
	{/if}

	{if $journal->getLocalizedSetting('copyrightNotice')}
		<rights>{$journal->getLocalizedSetting('copyrightNotice')|strip|escape:"html"}</rights>
	{/if}

	<subtitle>{$description|strip|escape:"html"}</subtitle>

	{foreach name=sections from=$publishedArticles item=section key=sectionId}
		{foreach from=$section.articles item=article}
			<entry>
				{* required elements *}
				<id>{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}</id>
				<title>{$article->getArticleTitle()|strip|escape:"html"}</title>
				<updated>{$article->getLastModified()|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</updated>

				{* recommended elements *}

				{foreach from=$article->getAuthors() item=author name=authorList}
					<author>
						<name>{$author->getFullName()|strip|escape:"html"}</name>
						{if $author->getEmail()}
							<email>{$author->getEmail()|strip|escape:"html"}</email>
						{/if}
					</author>
				{/foreach}{* authors *}

				<link rel="alternate" href="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}" />

				{if $article->getArticleAbstract()}
					<summary type="html" xml:base="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}">{$article->getArticleAbstract()|strip|escape:"html"}</summary>
				{/if}

				{* optional elements *}
				{* <category/> *}
				{* <contributor/> *}

				<published>{$article->getDatePublished()|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</published>

				{* <source/> *}
				{* <rights/> *}
			</entry>
		{/foreach}{* articles *}
	{/foreach}{* sections *}
</feed>
