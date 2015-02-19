{**
 * plugins/citationFormats/refMan/citation.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Reference Manager citation format generator
 *
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
{if $article->getDatePublished()}
PY  - {$article->getDatePublished()|date_format:"%Y/%m/%d/"}
{/if}
TI  - {$article->getLocalizedTitle()|strip_tags}
JF  - {$journal->getLocalizedTitle()|escape}{if $issue}; {$issue->getIssueIdentification()|escape|strip_tags}{/if}

KW  - {$article->getLocalizedSubject()|replace:';':','|escape}
N2  - {$article->getLocalizedAbstract()|strip_tags|replace:"\n":" "|replace:"\r":" "}
UR  - {$articleUrl}
