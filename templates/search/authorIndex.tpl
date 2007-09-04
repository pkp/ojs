{**
 * authorIndex.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Index of published articles by author.
 *
 * $Id$
 *}
{assign var="pageTitle" value="search.authorIndex"}
{include file="common/header.tpl"}

<p>{foreach from=$alphaList item=letter}<a href="{url op="authors" searchInitial=$letter}">{if $letter == $searchInitial}<strong>{$letter}</strong>{else}{$letter}{/if}</a> {/foreach}<a href="{url op="authors"}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

<a name="authors"></a>

{iterate from=authors item=author}
	{assign var=lastFirstLetter value=$firstLetter}
	{assign var=firstLetter value=$author->getLastName()|String_substr:0:1}

	{if $lastFirstLetter != $firstLetter}
		<a name="{$firstLetter|escape}"></a>
		<h3>{$firstLetter|escape}</h3>
	{/if}

	<a href="{url op="authors" path="view" firstName=$author->getFirstName() middleName=$author->getMiddleName() lastName=$author->getLastName() affiliation=$author->getAffiliation()}">
		{$author->getLastName(true)|escape},
		{$author->getFirstName()|escape}{if $author->getMiddleName()} {$author->getMiddleName()|escape}{/if}{if $author->getAffiliation()}, {$author->getAffiliation()|escape}{/if}
	</a>
	<br/>
{/iterate}
{if !$authors->wasEmpty()}
	<br />
	{page_info iterator=$authors}&nbsp;&nbsp;&nbsp;&nbsp;{page_links anchor="authors" iterator=$authors name="authors" searchInitial=$searchInitial}
{else}
{/if}

{include file="common/footer.tpl"}
