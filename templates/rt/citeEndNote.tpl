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

{assign var=escapedArticleId value=$articleId|escape}
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
	%U {$pageUrl}/article/view/{$escapedArticleId}/{$galleyId}
	
