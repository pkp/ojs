{**
 * citation.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- Capture Citation for ABNT
 *
 * $Id$
 *}
<div class="separator"></div>

{assign var=authors value=$article->getAuthors()}
{assign var=authorCount value=$authors|@count}
{foreach from=$authors item=author name=authors key=i}
	{assign var=firstName value=$author->getFirstName()}
	{$author->getLastName()|escape}, {$firstName[0]|escape}.{if $i<$authorCount-1}; {/if}{/foreach}.
{$article->getArticleTitle()|strip_unsafe_html}.
<strong>{$journal->getJournalTitle()|escape}</strong>, {translate key="plugins.citationFormat.acao.location"}{if $issue}, {$issue->getVolume()|escape}{/if}
{$article->getDatePublished()|date_format:'%e %m %Y'}.

