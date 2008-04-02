{**
 * atom.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Atom feed template
 *
 * $Id$
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<feed xmlns="http://www.w3.org/2005/Atom">
	{* required elements *}
	<id>{$selfUrl}</id>
	<title>{$journal->getJournalTitle()|escape:"html"|strip|strip_tags}: {translate key="announcement.announcements"}</title>
	<updated>{$dateUpdated|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</updated>

	{* recommended elements *}
	{* <author/> *}
	<link rel="alternate" href="{$journal->getUrl()}" />
	<link rel="self" type="application/atom+xml" href="{$selfUrl}" />

	{* optional elements *}
	{* <category/> *}
	{* <contributor/> *}
	<generator uri="http://pkp.sfu.ca/ojs/" version="{$ojsVersion|escape}">Open Journal Systems</generator>
	{if $journal->getJournalDescription()}
		{assign var="description" value=$journal->getJournalDescription()}
	{elseif $journal->getLocalizedSetting('searchDescription')}
		{assign var="description" value=$journal->getLocalizedSetting('searchDescription')}
	{/if}
	{if $description}
	<subtitle>{$description|strip|strip_tags|escape:"html"}</subtitle>
	{/if}

{foreach from=$announcements item=announcement}
	<entry>
		{* required elements *}
		<id>{url page="announcement" op="view" path=$announcement->getAnnouncementId()}</id>
		<title>{$announcement->getAnnouncementTitleFull()|strip|strip_tags|escape:"html"}</title>
		<updated>{$announcement->getDatetimePosted()|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</updated>
	  	<author>
			<name>{$journal->getJournalTitle()|escape:"html"|strip|strip_tags}</name>
        </author>
		<link rel="alternate" href="{url page="announcement" op="view" path=$announcement->getAnnouncementId()}" />
        {if $announcement->getAnnouncementDescription()}
		<summary type="html" xml:base="{url page="announcement" op="view" path=$announcement->getAnnouncementId()}">{$announcement->getAnnouncementDescription()|strip|strip_tags|escape:"html"}</summary>
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
