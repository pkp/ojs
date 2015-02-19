{**
 * plugins/citationFormats/apa/citation.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- Capture Citation APA format
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

({if $article->getDatePublished()}{$article->getDatePublished()|date_format:'%Y'}{elseif $issue->getDatePublished()}{$issue->getDatePublished()|date_format:'%Y'}{else}{$issue->getYear()|escape}{/if}).
{$article->getLocalizedTitle()}.
<em>{$journal->getLocalizedTitle()|capitalize}{if $issue}, {$issue->getVolume()|escape}</em>{if $issue->getNumber()}({$issue->getNumber()|escape}){/if}{else}</em>{/if}{if $article->getPages()}, {$article->getPages()}{/if}.
{if $article->getPubId('doi')}doi:<a href="http://dx.doi.org/{$article->getPubId('doi')|escape}">http://dx.doi.org/{$article->getPubId('doi')}</a>{else}{translate key="plugins.citationFormats.apa.retrieved" retrievedDate=$smarty.now|date_format:$dateFormatLong url=$articleUrl}{/if}
</div>
