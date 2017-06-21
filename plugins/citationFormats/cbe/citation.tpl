{**
 * plugins/citationFormats/cbe/citation.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- Capture Citation CBE format
 *
 *}
{assign var=authors value=$article->getAuthors()}
{assign var=authorCount value=$authors|@count}
{foreach from=$authors item=author name=authors key=i}
	{assign var=firstName value=$author->getFirstName()}
	{$author->getLastName()|escape}, {$firstName|String_substr:0:1|escape}.{if $i==$authorCount-2}, &amp; {elseif $i<$authorCount-1}, {/if}
{/foreach}

{if $article->getDatePublished()}{$article->getDatePublished()|date_format:'%Y %b %e'}{elseif $issue->getDatePublished()}{$issue->getDatePublished()|date_format:'%Y %b %e'}{else}{$issue->getYear()|escape}{/if}. {$article->getLocalizedTitle()|strip_unsafe_html}. {$journal->getLocalizedName()|escape}. [{translate key="rt.captureCite.online"}] {if $issue}{if $issue->getShowVolume()}{$issue->getVolume()|escape}{/if}:{if $issue->getShowNumber()}{$issue->getNumber()|escape}{/if}{/if}
