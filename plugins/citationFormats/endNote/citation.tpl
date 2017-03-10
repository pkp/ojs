{**
 * plugins/citationFormats/endNote/citation.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * EndNote citation format generator
 *
 *}
{if $galley}
	{url|assign:"articleUrl" page="article" op="version" path=$article->getBestArticleId()|to_array:$version:$galley->getBestGalleyId()}
{else}
	{url|assign:"articleUrl" page="article" op="version" path=$article->getBestArticleId()|to_array:$version}
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
{if count($article->getPageArray()) > 0}%P {foreach from=$article->getPageArray() item=range name=pages}{$range[0]|escape}{if $range[1]}-{$range[1]|escape}{if !$smarty.foreach.pages.last},{/if}{/if}{/foreach}
{* explicit newline required because Smarty foreach eats the one we expect *}{"\n"}{/if}
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

