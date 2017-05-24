{**
 * plugins/citationFormats/GOST/citation.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * With contributions from by Lepidus Tecnologia
 *
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- Capture Citation for GOST
 * 
 *}

{if $galley}
	{url|assign:"articleUrl" page="article" op="view" path=$article->getBestArticleId()|to_array:$galley->getBestGalleyId()}
{else}
	{url|assign:"articleUrl" page="article" op="view" path=$article->getBestArticleId()}
{/if}
 
{assign var=authors value=$article->getAuthors()}
{assign var=authorCount value=$authors|@count}
{assign var=location value=$citationPlugin->getLocalizedLocation($journal)}
{capture assign=iv}{translate key="issue.volume"}{/capture}
{capture assign=pn}{translate key="common.pageNumber"}{/capture}
{capture assign=sa}{translate key="submission.shortAuthor"}{/capture}
{if $authorCount <= 3}
	{foreach from=$authors item=author name=authors key=i}
		{assign var=firstName value=$author->getFirstName()}
		{assign var=middleName value=$author->getMiddleName()}
		{$author->getLastName()|escape} {$firstName|escape|mb_substr:0:1}.{if $middleName} {$middleName|escape|mb_substr:0:1}.{/if}{if $i<$authorCount-1}, {/if}{/foreach}
{else}
	{assign var=firstName value=$authors[0]->getFirstName()}
	{assign var=middleName value=$authors[0]->getMiddleName()}
	{$authors[0]->getLastName()|escape} {$firstName|escape|mb_substr:0:1}.{if $middleName} {$middleName|escape|mb_substr:0:1}.{/if} {$sa|mb_substr:10:10}
{/if}
{$article->getLocalizedTitle()|strip_unsafe_html} // 
<strong>{$journal->getLocalizedName()|escape}</strong>. {$article->getDatePublished()|date_format:'%Y'}{if $issue}{if $issue->getShowVolume()}. {$iv|mb_substr:0:1}. {$issue->getVolume()|escape}{/if}{if $issue->getShowNumber()}, № {$issue->getNumber()|escape}{/if}{/if}
{if $article->getPages()}. {$pn|mb_substr:0:1}. {$article->getPages()|escape}{/if}.
{if $article->getStoredPubId('doi')}doi:{$article->getStoredPubId('doi')}{else}{translate key="plugins.citationFormats.GOST.retrieved" retrievedDate=$smarty.now|date_format:"%d.%m.%Y" url=$articleUrl}{/if}
