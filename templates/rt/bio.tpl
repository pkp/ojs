{**
 * bio.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- author bio page.
 *
 * $Id$
 *}
{strip}
{assign var=pageTitle value="rt.authorBio"}
{include file="rt/header.tpl"}
{/strip}

<h3>{$article->getLocalizedTitle()|strip_unsafe_html}</h3>

{foreach from=$article->getAuthors() item=author name=authors}
<div id="authorBio">
<p>
	<em>{$author->getFullName()|escape}</em><br />
	{if $author->getUrl()}<a href="{$author->getUrl()|escape:"quotes"}">{$author->getUrl()|escape}</a><br/>{/if}
	{if $author->getLocalizedAffiliation()}{$author->getLocalizedAffiliation()|escape}{/if}
	{if $author->getCountry()}<br/>{$author->getCountryLocalized()|escape}{/if}
</p>

<p>{$author->getLocalizedBiography()|strip_unsafe_html|nl2br}</p>
</div>
{if !$smarty.foreach.authors.last}<div class="separator"></div>{/if}

{/foreach}

{include file="rt/footer.tpl"}

