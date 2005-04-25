{**
 * bio.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- author bio page.
 *
 * $Id$
 *}

{assign var=pageTitle value="rt.aboutAuthor"}

{include file="rt/header.tpl"}

<h3>"{$article->getArticleTitle()}"</h3>

{foreach from=$article->getAuthors() item=author name=authors}
<p>
	<i>{$author->getFullName()}</i><br />
	{if $author->getAffiliation()}{$author->getAffiliation()}{/if}
</p>

<p>{$author->getBiography()|nl2br}</p>

{if !$smarty.foreach.authors.last}<div class="separator"></div>{/if}

{/foreach}

{include file="rt/footer.tpl"}
