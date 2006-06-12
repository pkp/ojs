{**
 * citeProCite.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * ProCite citation format generator
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
JF  - {$journal->getTitle()}; {$issue->getIssueIdentification()}
Y2  - {$article->getDatePublished()|date_format:"%Y"}
KW  - {$article->getSubject()|escape}
N2  - {$article->getArticleAbstract()|strip_tags|replace:"\n":" "|replace:"\r":" "}
UR  - {$articleUrl}

