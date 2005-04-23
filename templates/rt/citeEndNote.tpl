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

{foreach from=$article->getAuthors() item=author}
	%A {$author->getFullName(true)}
{/foreach}
	%D {$article->getDatePublished()|date_format:"%Y"}
	%T {$article->getArticleTitle()}
	%B {$article->getDatePublished()|date_format:"%Y"}
	%9 {$article->getSubject()}
	%! {$article->getArticleTitle()}
	%K {$article->getSubject()}
	%X {$article->getAbstract()}
	%U {$pageUrl}/article/view/{$articleId}/{$galleyId}
	
