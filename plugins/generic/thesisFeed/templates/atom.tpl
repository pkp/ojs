{**
 * atom.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
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
	<title>{$journal->getJournalTitle()|escape:"html"|strip}: {translate key="plugins.generic.thesis.manager.theses"}</title>
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
	<subtitle>{$description|strip|escape:"html"}</subtitle>
	{/if}

{assign var="break" value="<br />"|escape:"html"}
{assign var="urlOpen" value="<a href=\"URL\">"}
{assign var="urlClose" value="</a>"|escape:html}

{foreach from=$theses item=thesis}
	<entry>
		{* required elements *}
		<id>{url page="thesis" op="view" path=$thesis->getThesisId()}</id>
		<title>{$thesis->getTitle()|strip|escape:"html"}</title>
		<updated>{$thesis->getDateSubmitted()|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</updated>
	  	<author>
			<name>{$thesis->getStudentFullName()|escape:"html"|strip}</name>
        </author>
		<link rel="alternate" href="{url page="thesis" op="view" path=$thesis->getThesisId()}" />

		{if $thesis->getUrl()}
			{assign var="thesisUrlOpen" value=$urlOpen|replace:"URL":$thesis->getUrl()|escape:"html"}
		{else}
			{assign var="thesisUrlOpen" value=""}
		{/if}

		<summary type="html" xml:base="{url page="thesis" op="view" path=$thesis->getThesisId()}">{$thesis->getDepartment()|strip|escape:"html"}, {$thesis->getUniversity()|strip|escape:"html"}{$break}{$thesis->getDateApproved()|date_format:"%B, %Y"}{$break}{$break}{if $thesisUrlOpen}{$thesisUrlOpen}{translate key="plugins.generic.thesis.fullText"}{$urlClose}{$break}{$break}{/if}{$thesis->getAbstract()|strip|escape:"html"}</summary>

		{* optional elements *}
		{* <category/> *}
		{* <contributor/> *}
		<published>{$thesis->getDateSubmitted()|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</published>
		{* <source/> *}
		{* <rights/> *}
	</entry>
{/foreach}
</feed>
