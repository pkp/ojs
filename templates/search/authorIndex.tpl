{**
 * authorIndex.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Index of published articles by author.
 *
 * $Id$
 *}

{assign var="pageTitle" value="search.authorIndex"}
{include file="common/header.tpl"}

{iterate from=authors item=author}
	{assign var=lastFirstLetter value=$firstLetter}
	{assign var=firstLetter value=$author->getLastName()}
	{assign var=firstLetter value=$firstLetter[0]}

	{if $lastFirstLetter != $firstLetter}
		<br />
		<a name="{$firstLetter}"></a>
		<h3>{$firstLetter}</h3>
	{/if}

	<a href="{$requestPageUrl}/authors/view?firstName={$author->getFirstName()|escape:'url'}&middleName={$author->getMiddleName()|escape:'url'}&lastName={$author->getLastName()|escape:'url'}&affiliation={$author->getAffiliation()|escape:'url'}">
		{$author->getLastName(true)},
		{$author->getFirstName()}{if $author->getMiddleName()} {$author->getMiddleName}{/if}{if $author->getAffiliation()}, {$author->getAffiliation()}{/if}
	</a>
	<br/>
{/iterate}
{if !$authors->wasEmpty()}
	<br />
	{page_info iterator=$authors}&nbsp;&nbsp;&nbsp;&nbsp;{page_links iterator=$authors name="authors"}
{else}
{/if}

{include file="common/footer.tpl"}
