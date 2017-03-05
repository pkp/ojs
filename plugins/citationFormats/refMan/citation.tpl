{**
 * plugins/citationFormats/refMan/citation.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Reference Manager citation format generator
 *
 *}
{if $galley}
	{url|assign:"articleUrl" page="article" op="view" path=$article->getBestArticleId()|to_array:$galley->getBestGalleyId()}
{else}
	{url|assign:"articleUrl" page="article" op="view" path=$article->getBestArticleId()}
{/if}
TY  - JOUR
{foreach from=$article->getAuthors() item=author}
AU  - {$author->getFullName(true)|escape}
{/foreach}
{if $article->getDatePublished()}
PY  - {$article->getDatePublished()|date_format:"%Y/%m/%d"}
{/if}
TI  - {$article->getLocalizedTitle()|strip_tags}
JF  - {$journal->getLocalizedName()|escape}{if $issue}; {$issue->getIssueIdentification()|escape|strip_tags}{/if}
{if $article->getStoredPubId('doi')}DO  - {$article->getStoredPubId('doi')|escape}
{/if}

KW  - {$article->getLocalizedSubject()|replace:';':','|escape}
N2  - {$article->getLocalizedAbstract()|strip_tags|replace:"\n":" "|replace:"\r":" "}
UR  - {$articleUrl}
