{**
 * bio.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- author bio page.
 *
 * $Id$
 *}

{assign var=pageTitle value="rt.authorBio"}

{include file="rt/header.tpl"}

<h3>{$article->getArticleTitle()|strip_unsafe_html}</h3>

{foreach from=$article->getAuthors() item=author name=authors}
<p>
	<i>{$author->getFullName()|escape}</i><br />
	{if $author->getUrl()}<a href="{$author->getUrl()|escape:"quotes"}">{$author->getUrl()|escape}</a><br/>{/if}
	{if $author->getAffiliation()}{$author->getAffiliation()|escape}{/if}
	{if $author->getCountry()}<br/>{$author->getCountryLocalized()|escape}{/if}
</p>

<p>{$author->getBiography()|strip_unsafe_html|nl2br}</p>

{if !$smarty.foreach.authors.last}<div class="separator"></div>{/if}

{/foreach}

{include file="rt/footer.tpl"}
