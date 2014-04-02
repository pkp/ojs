{**
 * plugins/citationFormats/turabian/citation.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- Capture Citation
 *
 *}
<div class="separator"></div>

<div id="citation">
{assign var=authors value=$article->getAuthors()}
{assign var=authorCount value=$authors|@count}
{foreach from=$authors item=author name=authors key=i}
	{assign var=firstName value=$author->getFirstName()}
	{$author->getLastName()|escape}, {$firstName|escape}{if $i==$authorCount-2}, {translate key="rt.context.and"} {elseif $i<$authorCount-1}, {else}.{/if}
{/foreach}

"{$article->getLocalizedTitle()|strip_unsafe_html}" <em>{$journal->getLocalizedTitle()|escape}</em> [{translate key="rt.captureCite.online"}], {if $issue && $issue->getVolume()}{translate key="issue.volume"} {$issue->getVolume()|escape}{/if}{if $issue && $issue->getNumber()} {translate key="issue.number"} {$issue->getNumber()|escape} {/if}({if $article->getDatePublished()}{$article->getDatePublished()|date_format:'%e %B %Y'|trim}{elseif $issue->getDatePublished()}{$issue->getDatePublished()|date_format:'%e %B %Y'|trim}{else}{$issue->getYear()|escape}{/if})
</div>
