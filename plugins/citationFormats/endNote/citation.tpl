{**
 * citation.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
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
%9 {$article->getArticleSubject()|escape}
%! {$article->getArticleTitle()|strip_tags}
%K {$article->getArticleSubject()|escape}
%X {$article->getArticleAbstract()|strip_tags|replace:"\n":" "|replace:"\r":" "}
%U {$articleUrl}

