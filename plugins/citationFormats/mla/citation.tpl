{**
 * plugins/citationFormats/mla/citation.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- Capture Citation MLA format
 *
 *}
{assign var=authors value=$article->getAuthors()}
{assign var=authorCount value=$authors|@count}
{foreach from=$authors item=author name=authors key=i}
{if $smarty.foreach.authors.first}{$author->getLastName()|escape}, {$author->getFirstName()|escape}{else}{$author->getFullName()|escape}{/if}{if $i==$authorCount-2}, & {elseif $i lt $authorCount-1}, {else}.{/if}
{/foreach}

"{$article->getLocalizedTitle()|strip_unsafe_html}." <em>{$journal->getLocalizedName()|escape}</em> [{translate key="rt.captureCite.online"}],{if $issue}{if $issue->getShowVolume()} {$issue->getVolume()|escape}{/if}{/if}{if $issue && $issue->getShowNumber() && $issue->getNumber()}.{$issue->getNumber()}{/if}{if $issue} ({$issue->getYear()}){/if}: {if $article->getPages()}{$article->getPages()}.{else}{translate key="plugins.citationFormats.mla.noPages"}{/if} {translate key="rt.captureCite.web"}. {$smarty.now|date_format:'%e %b. %Y'}
