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

{foreach from=$authors item=authorArray key=firstLetter}
	<a name="{$firstLetter}"></a>
	{if $authorArray|@count > 0}
		<h3>{$firstLetter}</h3>

		{foreach from=$authorArray item=author}
			<a href="{$requestPageUrl}/authors/view?firstName={$author->getFirstName()|escape:'url'}&middleName={$author->getMiddleName()|escape:'url'}&lastName={$author->getLastName()|escape:'url'}&affiliation={$author->getAffiliation()|escape:'url'}">
				{$author->getLastName(true)},
				{$author->getFirstName()}{if $author->getMiddleName()} {$author->getMiddleName}{/if}{if $author->getAffiliation()}, {$author->getAffiliation()}{/if}
			</a>
			<br/>
		{/foreach}
	{/if}
{/foreach}

{include file="common/footer.tpl"}
