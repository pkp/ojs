{**
 * citation.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- Capture Citation APA format
 *
 * $Id$
 *}

<div class="separator"></div>

{assign var=authors value=$article->getAuthors()}
{assign var=authorCount value=$authors|@count}
{foreach from=$authors item=author name=authors key=i}
	{assign var=firstName value=$author->getFirstName()}
	{$author->getLastName()|escape}, {$firstName[0]|escape}.{if $i==$authorCount-2}, &amp; {elseif $i<$authorCount-1}, {/if}
{/foreach}

({$article->getDatePublished()|date_format:'%Y'}).
{$article->getArticleTitle()|strip_unsafe_html}.
<i>{$journal->getJournalTitle()|escape}{if $issue}, {$issue->getVolume()|escape}</i>({$issue->getNumber()|escape}){else}</i>{/if}.
{translate key="plugins.citationFormats.apa.retrieved" retrievedDate=$smarty.now|date_format:$dateFormatShort url=$articleUrl}

