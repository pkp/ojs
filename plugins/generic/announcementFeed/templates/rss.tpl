{**
 * plugins/generic/announcementFeed/templates/rss.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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

	<channel rdf:about="{url journal=$journal->getPath()}">
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
		<dc:language>{$language|escape}</dc:language>

		<items>
			{foreach from=$announcements item=announcement}
			<rdf:Seq>
				<rdf:li rdf:resource="{url page="announcement" op="view" path=$announcement->id}"/>
			</rdf:Seq>
			{/foreach}
		</items>
	</channel>

{foreach from=$announcements item=announcement}
	<item rdf:about="{url page="announcement" op="view" path=$announcement->id}">
		{* required elements *}
		<title>{$announcement->getLocalizedData('fullTitle')|strip|escape:"html"}</title>
		<link>{url page="announcement" op="view" path=$announcement->id}</link>

		{* optional elements *}
		{if $announcement->getLocalizedData('description')}
		<description>{$announcement->getLocalizedData('description')|strip|escape:"html"}</description>
		{/if}
		<dc:creator>{$journal->getLocalizedName()|strip|escape:"html"}</dc:creator>
		<dc:date>{$announcement->datePosted->format("%Y-%m-%d")}</dc:date>
	</item>
{/foreach}

</rdf:RDF>
