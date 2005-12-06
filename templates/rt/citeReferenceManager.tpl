{**
 * citeReferenceManager.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Reference Manager citation format generator
 *
 * $Id$
 *}

{if $galleyId}
	{url|assign:"articleUrl" page="article" op="view" path=$articleId|to_array:$galleyId}
{else}
	{url|assign:"articleUrl" page="article" op="view" path=$articleId}
{/if}
	TY  - JOUR
{foreach from=$article->getAuthors() item=author}
	AU  - {$author->getFullName(true)|escape}
{/foreach}
	PY  - {$article->getDatePublished()|date_format:"%Y"}
	TI  - {$article->getArticleTitle()|strip_tags}
	JF  - {$journal->getTitle()|escape}; {$issue->getIssueIdentification()|escape}
	Y2  - {$article->getDatePublished()|date_format:"%Y"}
	KW  - {$article->getSubject()|replace:';':','|escape}
	N2  - {$article->getArticleAbstract()|escape}
	UR  - {$articleUrl}
	
