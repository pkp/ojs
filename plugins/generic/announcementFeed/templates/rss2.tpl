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
		<title>{$journal->getJournalTitle()|escape:"html"|strip|strip_tags}: {translate key="announcement.announcements"}</title>
		<link>{$journal->getUrl()}</link>
		{if $journal->getJournalDescription()}
			{assign var="description" value=$journal->getJournalDescription()}
		{elseif $journal->getLocalizedSetting('searchDescription')}
			{assign var="description" value=$journal->getLocalizedSetting('searchDescription')}
		{/if}
		<description>{$description|escape:"html"|strip|strip_tags}</description>

		{* optional elements *}
	    {if $journal->getPrimaryLocale()}
	    <language>{$journal->getPrimaryLocale()|replace:'_':'-'|strip|strip_tags|escape:"html"}</language>
	    {/if}
		<pubDate>{$dateUpdated|date_format:"%a, %d %b %Y %T %z"}</pubDate>
		<generator>OJS {$ojsVersion|escape}</generator>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>
		<ttl>60</ttl>

		{foreach from=$announcements item=announcement}
			<item>
				{* required elements *}
				<title>{$announcement->getAnnouncementTitleFull()|strip|strip_tags|escape:"html"}</title>
				<link>{url page="announcement" op="view" path=$announcement->getAnnouncementId()}</link>
				<description>{$announcement->getAnnouncementDescription()|strip|strip_tags|escape:"html"}</description>

				{* optional elements *}
				<guid isPermaLink="true">{url page="announcement" op="view" path=$announcement->getAnnouncementId()}</guid>
				<pubDate>{$announcement->getDatetimePosted()|date_format:"%a, %d %b %Y %T %z"}</pubDate>
			</item>
		{/foreach}
	</channel>
</rss>
