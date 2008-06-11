{**
 * rss.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RSS feed template
 *
 * $Id$
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<rdf:RDF
	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	xmlns="http://purl.org/rss/1.0/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:prism="http://prismstandard.org/namespaces/1.2/basic/">
    
	<channel rdf:about="{$journal->getUrl()}">
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
		<dc:language>{$journal->getPrimaryLocale()|replace:'_':'-'|escape:"html"|strip|strip_tags}</dc:language>
		{/if}

		<items>
			{foreach from=$announcements item=announcement}
			<rdf:Seq>
				<rdf:li rdf:resource="{url page="announcement" op="view" path=$announcement->getAnnouncementId()}"/>
			</rdf:Seq>
			{/foreach}
		</items>
	</channel>

{foreach from=$announcements item=announcement}
	<item rdf:about="{url page="announcement" op="view" path=$announcement->getAnnouncementId()}">
		{* required elements *}
		<title>{$announcement->getAnnouncementTitleFull()|strip|strip_tags|escape:"html"}</title>
		<link>{url page="announcement" op="view" path=$announcement->getAnnouncementId()}</link>

		{* optional elements *}
		{if $announcement->getAnnouncementDescription()}
		<description>{$announcement->getAnnouncementDescription()|strip|strip_tags|escape:"html"}</description>
		{/if}
		<dc:creator>{$journal->getJournalTitle()|strip|strip_tags|escape:"html"}</dc:creator>
		<dc:date>{$announcement->getDatePosted()|date_format:"%Y-%m-%d"}</dc:date>
	</item>
{/foreach}

</rdf:RDF>
