{**
 * plugins/citationFormats/apa/citation.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- Capture Citation APA format
 *
 *}
{if $galley}
	{url|assign:"articleUrl" page="article" op="version" path=$article->getBestArticleId()|to_array:$version:$galley->getBestGalleyId()}
{else}
	{url|assign:"articleUrl" page="article" op="version" path=$article->getBestArticleId()|to_array:$version}
{/if}

{assign var=authors value=$article->getAuthors()}
{assign var=authorCount value=$authors|@count}
{foreach from=$authors item=author name=authors key=i}
	{assign var=firstName value=$author->getFirstName()}
	{$author->getLastName()|escape}, {$firstName|String_substr:0:1|escape}.{if $i==$authorCount-2}, &amp; {elseif $i<$authorCount-1}, {/if}
{/foreach}

({if $article->getDatePublished()}{$article->getDatePublished()|date_format:'%Y'}{elseif $issue->getDatePublished()}{$issue->getDatePublished()|date_format:'%Y'}{else}{$issue->getYear()|escape}{/if}).
{$article->getLocalizedTitle()}.
<em>{$journal->getLocalizedName()|capitalize}{if $issue}, {if $issue->getShowVolume()}{$issue->getVolume()|escape}{/if}</em>{if $issue->getNumber() && $issue->getShowNumber()}({$issue->getNumber()|escape}){/if}{else}</em>{/if}{if $article->getPages()}, {$article->getPages()}{/if}.
{if $article->getStoredPubId('doi')}doi:{$article->getStoredPubId('doi')}{else}{translate key="plugins.citationFormats.apa.retrieved" retrievedDate=$smarty.now|date_format:$dateFormatLong url=$articleUrl}{/if}
