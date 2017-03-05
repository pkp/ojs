{**
 * plugins/citationFormats/abnt/citation.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * With contributions from by Lepidus Tecnologia
 *
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- Capture Citation for ABNT
 *
 *}
{assign var=authors value=$article->getAuthors()}
{assign var=authorCount value=$authors|@count}
{assign var=location value=$citationPlugin->getLocalizedLocation($journal)}
{if $authorCount <= 3}
	{foreach from=$authors item=author name=authors key=i}
		{assign var=firstName value=$author->getFirstName()}
		{assign var=middleName value=$author->getMiddleName()}
		{$author->getLastName()|escape|mb_upper}, {$firstName|escape}{if $middleName} {$middleName|escape}{/if}{if $i<$authorCount-1}; {/if}{/foreach}.
{else}
	{assign var=firstName value=$authors[0]->getFirstName()}
	{assign var=middleName value=$authors[0]->getMiddleName()}
	{$authors[0]->getLastName()|escape|mb_upper}, {$firstName|escape}{if $middleName} {$middleName|escape}{/if} et al.
{/if}
{$article->getLocalizedTitle()|strip_unsafe_html}.
<strong>{$journal->getLocalizedName()|escape}</strong>, {$location|default:"[S.l.]"|escape}{if $issue}{if $issue->getShowVolume()}, v. {$issue->getVolume()|escape}{/if}{if $issue->getShowNumber()}, n. {$issue->getNumber()|escape}{/if}{/if}
{if $article->getPages()}, p. {$article->getPages()|escape}{/if}, {if $article->getDatePublished()}{$article->getDatePublished()|abnt_date_format|lower}{elseif $issue->getDatePublished()}{$issue->getDatePublished()|abnt_date_format}{else}{$issue->getYear()|escape}{/if}.
{if $currentJournal->getSetting('onlineIssn')}ISSN {$currentJournal->getSetting('onlineIssn')|escape}.
{elseif $currentJournal->getSetting('printIssn')}ISSN {$currentJournal->getSetting('printIssn')|escape}. {/if}
{capture assign=articleUrl}{url page="article" op="view" path=$article->getBestArticleId()}{/capture}
{translate key="plugins.citationFormats.abnt.retrieved" retrievedDate=$smarty.now|abnt_date_format_with_day url=$articleUrl}
{if $article->getStoredPubId('doi')}doi: <a href="https://doi.org/{$article->getStoredPubId('doi')|escape}">https://doi.org/{$article->getStoredPubId('doi')|escape}</a>. {/if}
