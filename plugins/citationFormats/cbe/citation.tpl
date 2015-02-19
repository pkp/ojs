{**
 * plugins/citationFormats/cbe/citation.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- Capture Citation CBE format
 *
 *}
<div class="separator"></div>

<div id="citation">
{assign var=authors value=$article->getAuthors()}
{assign var=authorCount value=$authors|@count}
{foreach from=$authors item=author name=authors key=i}
	{assign var=firstName value=$author->getFirstName()}
	{$author->getLastName()|escape}, {$firstName|escape|truncate:1:"":true}.{if $i==$authorCount-2}, &amp; {elseif $i<$authorCount-1}, {/if}
{/foreach}

{if $article->getDatePublished()}{$article->getDatePublished()|date_format:'%Y %b %e'}{elseif $issue->getDatePublished()}{$issue->getDatePublished()|date_format:'%Y %b %e'}{else}{$issue->getYear()|escape}{/if}. {$article->getLocalizedTitle()|strip_unsafe_html}. {$journal->getLocalizedTitle()|escape}. [{translate key="rt.captureCite.online"}] {if $issue}{$issue->getVolume()|escape}:{$issue->getNumber()|escape}{/if}
</div>
