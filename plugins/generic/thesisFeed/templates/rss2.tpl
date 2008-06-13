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
	    <language>{$journal->getPrimaryLocale()|replace:'_':'-'|strip|escape:"html"}</language>
	    {/if}
		<pubDate>{$dateUpdated|date_format:"%a, %d %b %Y %T %z"}</pubDate>
		<generator>OJS {$ojsVersion|escape}</generator>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>
		<ttl>60</ttl>

		{assign var="break" value="<br />"|escape:"html"}
		{assign var="urlOpen" value="<a href=\"URL\">"}
		{assign var="urlClose" value="</a>"|escape:html}

		{foreach from=$theses item=thesis}
			<item>
				{* required elements *}
				<title>{$thesis->getTitle()|strip|escape:"html"}</title>
				<link>{url page="thesis" op="view" path=$thesis->getThesisId()}</link>

				{if $thesis->getUrl()}
					{assign var="thesisUrlOpen" value=$urlOpen|replace:"URL":$thesis->getUrl()|escape:"html"}
				{else}
					{assign var="thesisUrlOpen" value=""}
				{/if}

				<description>{$thesis->getDepartment()|strip|escape:"html"}, {$thesis->getUniversity()|strip|escape:"html"}{$break}{$thesis->getDateApproved()|date_format:"%B, %Y"}{$break}{$break}{if $thesisUrlOpen}{$thesisUrlOpen}{translate key="plugins.generic.thesis.fullText"}{$urlClose}{$break}{$break}{/if}{$thesis->getAbstract()|strip|escape:"html"}</description>

				{* optional elements *}
				<guid isPermaLink="true">{url page="thesis" op="view" path=$thesis->getThesisId()}</guid>
				<pubDate>{$thesis->getDateSubmitted()|date_format:"%a, %d %b %Y %T %z"}</pubDate>
			</item>
		{/foreach}
	</channel>
</rss>
