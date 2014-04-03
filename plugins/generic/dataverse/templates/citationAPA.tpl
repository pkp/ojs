{**
 * plugins/generic/dataverse/templates/citationAPA.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Generate an APA-formatted article citation to include in Dataverse study metadata
 *
 *}
{assign var=authors value=$article->getAuthors()}
{assign var=authorCount value=$authors|@count}
{foreach from=$authors item=author name=authors key=i}
	{assign var=firstName value=$author->getFirstName()}
	{$author->getLastName()|escape}, {$firstName|escape|truncate:1:"":true}.{if $i==$authorCount-2}, &amp; {elseif $i<$authorCount-1}, {/if}
{/foreach}
{if $article->getStatus()==$smarty.const.STATUS_PUBLISHED}
  ({if $publishedArticle->getDatePublished()}{$publishedArticle->getDatePublished()|date_format:'%Y'}{elseif $issue->getDatePublished()}{$issue->getDatePublished()|date_format:'%Y'}{else}{$issue->getYear()|escape}{/if}).
{else}
  {translate key="plugins.generic.dataverse.citationAPA.inPress"}
{/if}
{$article->getLocalizedTitle()}.
<em>{$journal->getLocalizedTitle()|capitalize}{if $issue}, {$issue->getVolume()|escape}</em>{if $issue->getNumber()}({$issue->getNumber()|escape}){/if}{else}</em>{/if}{if $article->getPages()}, {$article->getPages()}{/if}.

