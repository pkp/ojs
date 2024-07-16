{**
 * plugins/generic/announcementFeed/templates/rss2.tpl
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * RSS 2 feed template
 *
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<rss version="2.0">
	<channel>
		{* required elements *}
		<title>{$journal->getLocalizedName()|strip|escape:"html"}: {translate key="announcement.announcements"}</title>
		<link>{url journal=$journal->getPath()}</link>
		{if $journal->getLocalizedDescription()}
			{assign var="description" value=$journal->getLocalizedDescription()}
		{elseif $journal->getLocalizedData('searchDescription')}
			{assign var="description" value=$journal->getLocalizedData('searchDescription')}
		{/if}
		<description>{$description|strip|escape:"html"}</description>

		{* optional elements *}
		<language>{$language|escape}</language>

	{capture assign="dateUpdated"}{$dateUpdated|strtotime}{/capture}
		<pubDate>{$smarty.const.DATE_RSS|date:$dateUpdated}</pubDate>
		<generator>OJS {$ojsVersion|escape}</generator>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>
		<ttl>60</ttl>

		{foreach from=$announcements item=announcement}
			<item>
				{* required elements *}
				<title>{$announcement->getLocalizedData('fullTitle')|strip|escape:"html"}</title>
				<link>{url page="announcement" op="view" path=$announcement->id}</link>
				<description>{$announcement->getLocalizedData('description')|strip|escape:"html"}</description>

				{* optional elements *}
				<guid isPermaLink="true">{url page="announcement" op="view" path=$announcement->id}</guid>
				{capture assign="datePosted"}{$announcement->datePosted|strtotime}{/capture}
				<pubDate>{$smarty.const.DATE_RSS|date:$datePosted}</pubDate>
			</item>
		{/foreach}
	</channel>
</rss>
