{**
 * citeEndNote.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * EndNote citation format generator
 *
 * $Id$
 *}

{if $galleyId}
	{url|assign:"articleUrl" page="article" op="view" path=$articleId|to_array:$galleyId}
{else}
	{url|assign:"articleUrl" page="article" op="view" path=$articleId}
{/if}
{foreach from=$article->getAuthors() item=author}
	%A {$author->getFullName(true)|escape}
{/foreach}
	%D {$article->getDatePublished()|date_format:"%Y"}
	%T {$article->getArticleTitle()|strip_tags}
	%B {$article->getDatePublished()|date_format:"%Y"}
	%9 {$article->getSubject()|escape}
	%! {$article->getArticleTitle()|strip_tags}
	%K {$article->getSubject()|escape}
	%X {$article->getArticleAbstract()|escape}
	%U {$articleUrl}
	
