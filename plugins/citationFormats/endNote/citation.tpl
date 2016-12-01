{**
 * plugins/citationFormats/endNote/citation.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * EndNote citation format generator
 *
 *}
{if $galley}
	{url|assign:"articleUrl" page="article" op="view" path=$article->getBestArticleId()|to_array:$galley->getBestGalleyId()}
{else}
	{url|assign:"articleUrl" page="article" op="view" path=$article->getBestArticleId()}
{/if}
{foreach from=$article->getAuthors() item=author}
%A {$author->getFullName(true)|escape}
{/foreach}
{if $article->getDatePublished()}
%D {$article->getDatePublished()|date_format:"%Y"}
{elseif $issue->getDatePublished()}
%D {$issue->getDatePublished()|date_format:"%Y"}
{else}
%D {$issue->getYear()|escape}
{/if}
%T {$article->getLocalizedTitle()|strip_tags}
%K {$article->getLocalizedSubject()|escape}
%X {$article->getLocalizedAbstract()|strip_tags|replace:"\n":" "|replace:"\r":" "}
%U {$articleUrl}
%J {$currentJournal->getLocalizedName()|escape}
%0 Journal Article
{if $article->getStoredPubId('doi')}%R {$article->getStoredPubId('doi')|escape}
{/if}
{if $article->getStartingPage()}%P {$article->getStartingPage()|escape}{if $article->getEndingPage()}-{$article->getEndingPage()|escape}
{/if}{/if}
{if $issue->getShowVolume()}%V {$issue->getVolume()|escape}
{/if}
{if $issue->getShowNumber()}%N {$issue->getNumber()|escape}
{/if}
{if $currentJournal->getSetting('onlineIssn')}%@ {$currentJournal->getSetting('onlineIssn')|escape}
{elseif $currentJournal->getSetting('printIssn')}%@ {$currentJournal->getSetting('printIssn')|escape}
{/if}
{if $article->getDatePublished()}
%8 {$article->getDatePublished()|date_format:"%Y-%m-%d"}
{/if}

