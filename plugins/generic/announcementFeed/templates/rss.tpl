{**
 * plugins/generic/announcementFeed/templates/rss.tpl
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
		<dc:language>{$journal->getPrimaryLocale()|replace:'_':'-'|strip|escape:"html"}</dc:language>
		{/if}

		<items>
			{foreach from=$announcements item=announcement}
			<rdf:Seq>
				<rdf:li rdf:resource="{url page="announcement" op="view" path=$announcement->getId()}"/>
			</rdf:Seq>
			{/foreach}
		</items>
	</channel>

{foreach from=$announcements item=announcement}
	<item rdf:about="{url page="announcement" op="view" path=$announcement->getId()}">
		{* required elements *}
		<title>{$announcement->getLocalizedTitleFull()|strip|escape:"html"}</title>
		<link>{url page="announcement" op="view" path=$announcement->getId()}</link>

		{* optional elements *}
		{if $announcement->getLocalizedDescription()}
		<description>{$announcement->getLocalizedDescription()|strip|escape:"html"}</description>
		{/if}
		<dc:creator>{$journal->getLocalizedTitle()|strip|escape:"html"}</dc:creator>
		<dc:date>{$announcement->getDatePosted()|date_format:"%Y-%m-%d"}</dc:date>
	</item>
{/foreach}

</rdf:RDF>
