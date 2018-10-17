{**
 * plugins/generic/announcementFeed/templates/atom.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
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
	<generator uri="http://pkp.sfu.ca/ojs/" version="{$ojsVersion|escape}">Open Journal Systems</generator>
	{if $journal->getLocalizedDescription()}
		{assign var="description" value=$journal->getLocalizedDescription()}
	{elseif $journal->getLocalizedSetting('searchDescription')}
		{assign var="description" value=$journal->getLocalizedSetting('searchDescription')}
	{/if}
	{if $description}
	<subtitle>{$description|strip|escape:"html"}</subtitle>
	{/if}

{foreach from=$announcements item=announcement}
	<entry>
		{* required elements *}
		<id>{url page="announcement" op="view" path=$announcement->getId()}</id>
		<title>{$announcement->getLocalizedTitleFull()|strip|escape:"html"}</title>
		<updated>{$announcement->getDatetimePosted()|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</updated>
	  	<author>
			<name>{$journal->getLocalizedName()|strip|escape:"html"}</name>
        </author>
		<link rel="alternate" href="{url page="announcement" op="view" path=$announcement->getId()}" />
        {if $announcement->getLocalizedDescription()}
		<summary type="html" xml:base="{url page="announcement" op="view" path=$announcement->getId()}">{$announcement->getLocalizedDescription()|strip|escape:"html"}</summary>
        {/if}

		{* optional elements *}
		{* <category/> *}
		{* <contributor/> *}
		<published>{$announcement->getDatetimePosted()|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</published>
		{* <source/> *}
		{* <rights/> *}
	</entry>
{/foreach}
</feed>
