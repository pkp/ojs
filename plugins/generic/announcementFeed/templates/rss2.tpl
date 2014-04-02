{**
 * plugins/generic/announcementFeed/templates/rss2.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RSS 2 feed template
 *
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<rss version="2.0">
	<channel>
		{* required elements *}
		<title>{$journal->getLocalizedTitle()|strip|escape:"html"}: {translate key="announcement.announcements"}</title>
		<link>{$journal->getUrl()|escape}</link>
		{if $journal->getLocalizedDescription()}
			{assign var="description" value=$journal->getLocalizedDescription()}
		{elseif $journal->getLocalizedSetting('searchDescription')}
			{assign var="description" value=$journal->getLocalizedSetting('searchDescription')}
		{/if}
		<description>{$description|strip|escape:"html"}</description>

		{* optional elements *}
	    {if $journal->getPrimaryLocale()}
	    <language>{$journal->getPrimaryLocale()|replace:'_':'-'|strip|escape:"html"}</language>
	    {/if}
		<pubDate>{$dateUpdated|date_format:"%a, %d %b %Y %T %z"}</pubDate>
		<generator>OJS {$ojsVersion|escape}</generator>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>
		<ttl>60</ttl>

		{foreach from=$announcements item=announcement}
			<item>
				{* required elements *}
				<title>{$announcement->getLocalizedTitleFull()|strip|escape:"html"}</title>
				<link>{url page="announcement" op="view" path=$announcement->getId()}</link>
				<description>{$announcement->getLocalizedDescription()|strip|escape:"html"}</description>

				{* optional elements *}
				<guid isPermaLink="true">{url page="announcement" op="view" path=$announcement->getId()}</guid>
				<pubDate>{$announcement->getDatetimePosted()|date_format:"%a, %d %b %Y %T %z"}</pubDate>
			</item>
		{/foreach}
	</channel>
</rss>
