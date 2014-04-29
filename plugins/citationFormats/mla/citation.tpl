{**
 * plugins/citationFormats/mla/citation.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- Capture Citation MLA format
 *
 *}
<div class="separator"></div>

<div id="citation">
{assign var=authors value=$article->getAuthors()}
{assign var=authorCount value=$authors|@count}
{foreach from=$authors item=author name=authors key=i}
{if $smarty.foreach.authors.first}{$author->getLastName()|escape}, {$author->getFirstName()|escape}{else}{$author->getFullName()|escape}{/if}{if $i==$authorCount-2}, & {elseif $i lt $authorCount-1}, {else}.{/if}
{/foreach}

"{$article->getLocalizedTitle()|strip_unsafe_html}." <em>{$journal->getLocalizedTitle()|escape}</em> [{translate key="rt.captureCite.online"}],{if $issue} {$issue->getVolume()|escape}{/if}{if $issue && $issue->getNumber()}.{$issue->getNumber()}{/if}{if $issue} ({$issue->getYear()}){/if}: {if $article->getPages()}{$article->getPages()}.{else}{translate key="plugins.citationFormats.mla.noPages"}{/if} {translate key="rt.captureCite.web"}. {$smarty.now|date_format:'%e %b. %Y'}
</div>
