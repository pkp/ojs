{**
 * plugins/citationFormats/endNote/citation.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * EndNote citation format generator
 *
 *}
{if $galleyId}
	{url|assign:"articleUrl" page="article" op="view" path=$articleId|to_array:$galleyId}
{else}
	{url|assign:"articleUrl" page="article" op="view" path=$articleId}
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
%B {$article->getDatePublished()|date_format:"%Y"}
%9 {$article->getLocalizedSubject()|escape}
%! {$article->getLocalizedTitle()|strip_tags}
%K {$article->getLocalizedSubject()|escape}
%X {$article->getLocalizedAbstract()|strip_tags|replace:"\n":" "|replace:"\r":" "}
%U {$articleUrl}
%J {$currentJournal->getLocalizedName()|escape}
%0 Journal Article
{if $article->getPubId('doi')}%R {$article->getPubId('doi')|escape}
{/if}
{if $article->getPages()}%R {$article->getPages()|escape}
{/if}
{if $issue->getShowVolume()}%V {$issue->getVolume()|escape}
{/if}
{if $issue->getShowNumber()}%N {$issue->getNumber()|escape}
{/if}
{if $currentJournal->getSetting('onlineIssn')}%@ {$currentJournal->getSetting('onlineIssn')|escape}
{elseif $currentJournal->getSetting('printIssn')}%@ {$currentJournal->getSetting('printIssn')|escape}
{/if}

