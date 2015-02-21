{**
 * plugins/generic/thesisFeed/templates/atom.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Atom feed template
 *
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<feed xmlns="http://www.w3.org/2005/Atom">
	{* required elements *}
	<id>{$selfUrl|escape}</id>
	<title>{$journal->getLocalizedTitle()|strip|escape:"html"}: {translate key="plugins.generic.thesis.manager.theses"}</title>
	<updated>{$dateUpdated|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</updated>

	{* recommended elements *}
	{* <author/> *}
	<link rel="alternate" href="{$journal->getUrl()|escape}" />
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
		<subtitle type="html">{$description|strip|escape:"html"}</subtitle>
	{/if}

{assign var="break" value="<br />"|escape:"html"}
{assign var="urlOpen" value="<a href=\"URL\">"}
{assign var="urlClose" value="</a>"|escape:html}

{foreach from=$theses item=thesis}
	<entry>
		{* required elements *}
		<id>{url page="thesis" op="view" path=$thesis->getId()}</id>
		<title>{$thesis->getTitle()|strip|escape:"html"}</title>
		<updated>{$thesis->getDateSubmitted()|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</updated>
	 	<author>
			<name>{$thesis->getStudentFullName()|strip|escape:"html"}</name>
        </author>
		<link rel="alternate" href="{url page="thesis" op="view" path=$thesis->getId()}" />

		{if $thesis->getUrl()}
			{assign var="thesisUrlOpen" value=$urlOpen|replace:"URL":$thesis->getUrl()|escape:"html"}
		{else}
			{assign var="thesisUrlOpen" value=""}
		{/if}

		<summary type="html" xml:base="{url page="thesis" op="view" path=$thesis->getId()}">{$thesis->getDepartment()|strip|escape:"html"}, {$thesis->getUniversity()|strip|escape:"html"}{$break}{$thesis->getDateApproved()|date_format:"%B, %Y"}{$break}{$break}{if $thesisUrlOpen}{$thesisUrlOpen}{translate key="plugins.generic.thesis.fullText"}{$urlClose}{$break}{$break}{/if}{$thesis->getAbstract()|strip|escape:"html"}</summary>

		{* optional elements *}
		{* <category/> *}
		{* <contributor/> *}
		<published>{$thesis->getDateSubmitted()|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</published>
		{* <source/> *}
		{* <rights/> *}
	</entry>
{/foreach}
</feed>
