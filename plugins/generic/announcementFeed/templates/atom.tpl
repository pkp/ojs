{**
 * plugins/generic/announcementFeed/templates/atom.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Atom feed template
 *
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<feed xmlns="http://www.w3.org/2005/Atom">
	{* required elements *}
	<id>{$selfUrl|escape}</id>
	<title>{$journal->getLocalizedName()|escape:"html"|strip}: {translate key="announcement.announcements"}</title>
	<updated>{$dateUpdated|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</updated>

	{* recommended elements *}
	{* <author/> *}
	<link rel="alternate" href="{url journal=$journal->getPath()}" />
	<link rel="self" type="application/atom+xml" href="{$selfUrl|escape}" />

	{* optional elements *}
	{* <category/> *}
	{* <contributor/> *}
	<generator uri="https://pkp.sfu.ca/ojs/" version="{$ojsVersion|escape}">Open Journal Systems</generator>
	{if $journal->getLocalizedDescription()}
		{assign var="description" value=$journal->getLocalizedDescription()}
	{elseif $journal->getLocalizedData('searchDescription')}
		{assign var="description" value=$journal->getLocalizedData('searchDescription')}
	{/if}
	{if $description}
	<subtitle>{$description|strip|escape:"html"}</subtitle>
	{/if}

{foreach from=$announcements item=announcement}
	<entry>
		{* required elements *}
		<id>{url page="announcement" op="view" path=$announcement->id}</id>
		<title>{$announcement->getLocalizedData('fullTitle')|strip|escape:"html"}</title>
		<updated>{$announcement->datePosted->format("%Y-%m-%dT%T%z")|regex_replace:"/00$/":":00"}</updated>
	  	<author>
			<name>{$journal->getLocalizedName()|strip|escape:"html"}</name>
        </author>
		<link rel="alternate" href="{url page="announcement" op="view" path=$announcement->id}" />
        {if $announcement->getLocalizedData('description')}
		<summary type="html" xml:base="{url page="announcement" op="view" path=$announcement->id}">{$announcement->getLocalizedData('description')|strip|escape:"html"}</summary>
        {/if}

		{* optional elements *}
		{* <category/> *}
		{* <contributor/> *}
		<published>{$announcement->datePosted->format("%Y-%m-%dT%T%z")|regex_replace:"/00$/":":00"}</published>
		{* <source/> *}
		{* <rights/> *}
	</entry>
{/foreach}
</feed>
