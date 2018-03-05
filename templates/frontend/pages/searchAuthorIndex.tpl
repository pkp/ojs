{**
 * templates/frontend/pages/searchAuthorIndex.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Index of published articles by author.
 *
 *}
{strip}
{assign var="pageTitle" value="search.authorIndex"}
{include file="frontend/components/header.tpl"}
{/strip}

<p>{foreach from=$alphaList item=letter}<a href="{url op="authors" searchInitial=$letter}">{if $letter == $searchInitial}<strong>{$letter|escape}</strong>{else}{$letter|escape}{/if}</a> {/foreach}<a href="{url op="authors"}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

<div id="authors">
{iterate from=authors item=author}
	{assign var=lastFirstLetter value=$firstLetter}
	{assign var=firstLetter value=$author->getLastName()|String_substr:0:1}

	{if $lastFirstLetter|lower != $firstLetter|lower}
			<div id="{$firstLetter|escape}">
		<h3>{$firstLetter|escape}</h3>
			</div>
	{/if}

	{assign var=lastAuthorName value=$authorName}
	{assign var=lastAuthorCountry value=$authorCountry}

	{assign var=authorAffiliation value=$author->getLocalizedAffiliation()}
	{assign var=authorCountry value=$author->getCountry()}

	{assign var=authorFirstName value=$author->getFirstName()}
	{assign var=authorMiddleName value=$author->getMiddleName()}
	{assign var=authorLastName value=$author->getLastName()}
	{assign var=authorName value="$authorLastName, $authorFirstName"}

	{if $authorMiddleName != ''}{assign var=authorName value="$authorName $authorMiddleName"}{/if}
	{strip}
		<a href="{url op="authors" path="view" firstName=$authorFirstName middleName=$authorMiddleName lastName=$authorLastName affiliation=$authorAffiliation country=$authorCountry}">{$authorName|escape}</a>
		{if $authorAffiliation}, {$authorAffiliation|escape}{/if}
		{if $lastAuthorName == $authorName && $lastAuthorCountry != $authorCountry}
			{* Disambiguate with country if necessary (i.e. if names are the same otherwise) *}
			{if $authorCountry} ({$author->getCountryLocalized()}){/if}
		{/if}
	{/strip}
	<br/>
{/iterate}
{if !$authors->wasEmpty()}
	<br />
	{page_info iterator=$authors}&nbsp;&nbsp;&nbsp;&nbsp;{page_links anchor="authors" iterator=$authors name="authors" searchInitial=$searchInitial}
{else}
{/if}
</div>
{include file="frontend/components/footer.tpl"}

